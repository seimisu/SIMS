<?php

namespace App\Services\Notifications;

use App\Models\ListAgencies;
use App\Models\ScholarTerm;
use App\Models\Scholars;
use App\Models\studentLandbankRequest;
use App\Models\StudentProfileRequest;
use App\Models\User;
use App\Notifications\RoleBellNotification;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class RoleBellNotificationService
{
    public function notifyRegionalScholarSubmissions(): void
    {
        ScholarTerm::with('scholar.schoolInfo.campus.address', 'scholar.profile')
            ->where('verification_status', 'submitted')
            ->latest('updated_at')
            ->limit(50)
            ->get()
            ->each(fn ($term) => $this->notifyRegionalUsersForScholar(
                $term->scholar,
                'grade_submission_created',
                'New grade submission',
                $this->scholarName($term->scholar).' submitted grades for review.',
                '/scholar-submissions?tab=grades',
                'scholar_term_records',
                $term->id
            ));

        StudentProfileRequest::with('scholar.schoolInfo.campus.address', 'scholar.profile')
            ->where('status', 'pending')
            ->latest('updated_at')
            ->limit(50)
            ->get()
            ->each(fn ($request) => $this->notifyRegionalUsersForScholar(
                $request->scholar,
                'profile_request_created',
                'New profile update request',
                $this->scholarName($request->scholar).' submitted a profile update request.',
                '/scholar-submissions?tab=profile',
                'profile_requests',
                $request->id
            ));

        studentLandbankRequest::with('scholar.schoolInfo.campus.address', 'scholar.profile')
            ->where('status', 'pending')
            ->latest('updated_at')
            ->limit(50)
            ->get()
            ->each(fn ($request) => $this->notifyRegionalUsersForScholar(
                $request->scholar,
                'landbank_request_created',
                'New Landbank request',
                $this->scholarName($request->scholar).' submitted a Landbank request.',
                '/scholar-submissions?tab=landbank',
                'landbank_requests',
                $request->id
            ));
    }

    public function notifyScholarshipStaff(string $type, string $title, string $message, string $url, string $sourceTable, int|string $sourceId): void
    {
        $this->notifyUsers($this->scholarshipUsers(), compact('type', 'title', 'message', 'url', 'sourceTable', 'sourceId'));
    }

    public function notifyRegionalPayrollResult(string $region, string $type, string $title, string $message, string $url, string $sourceTable, int|string $sourceId): void
    {
        $this->notifyUsers($this->regionalUsersForAgencyName($region), compact('type', 'title', 'message', 'url', 'sourceTable', 'sourceId'));
    }

    public function notifyCashiers(string $type, string $title, string $message, string $url, string $sourceTable, int|string $sourceId): void
    {
        $this->notifyUsers($this->cashierUsers(), compact('type', 'title', 'message', 'url', 'sourceTable', 'sourceId'));
    }

    public function notifyRegionalAndScholarshipStaff(string $type, string $title, string $message, string $url, string $sourceTable, int|string $sourceId): void
    {
        $users = $this->regionalUsers()
            ->merge($this->scholarshipUsers())
            ->unique('id')
            ->values();

        $this->notifyUsers($users, compact('type', 'title', 'message', 'url', 'sourceTable', 'sourceId'));
    }

    private function notifyRegionalUsersForScholar(?Scholars $scholar, string $type, string $title, string $message, string $url, string $sourceTable, int|string $sourceId): void
    {
        $regionCode = $scholar?->schoolInfo
            ?->pluck('campus.address.region_code')
            ->filter()
            ->first();

        if (! $regionCode) {
            return;
        }

        $this->notifyUsers($this->regionalUsersForRegionCode($regionCode), compact('type', 'title', 'message', 'url', 'sourceTable', 'sourceId'));
    }

    private function notifyUsers(Collection|EloquentCollection $users, array $data): void
    {
        $users = $users->filter(fn ($user) => $user instanceof User && $user->is_active && ! $user->is_delete);

        foreach ($users as $user) {
            if ($this->notificationExists($user, $data['sourceTable'], $data['sourceId'], $data['type'])) {
                continue;
            }

            Notification::send($user, new RoleBellNotification([
                'type' => $data['type'],
                'title' => $data['title'],
                'message' => $data['message'],
                'url' => $data['url'],
                'source_table' => $data['sourceTable'],
                'source_id' => (string) $data['sourceId'],
            ]));
        }
    }

    private function notificationExists(User $user, string $sourceTable, int|string $sourceId, string $type): bool
    {
        return DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->whereRaw("(data::jsonb)->>'source_table' = ?", [$sourceTable])
            ->whereRaw("(data::jsonb)->>'source_id' = ?", [(string) $sourceId])
            ->whereRaw("(data::jsonb)->>'type' = ?", [$type])
            ->exists();
    }

    private function regionalUsers(): EloquentCollection
    {
        return User::with('profile.agency', 'role')
            ->whereHas('role', fn ($role) => $role->whereRaw('LOWER(name) IN (?, ?)', ['regional staff', 'regional supervisor']))
            ->get();
    }

    private function scholarshipUsers(): EloquentCollection
    {
        return User::with('role')
            ->whereHas('role', fn ($role) => $role->whereRaw('LOWER(name) IN (?, ?)', ['scholarship staff', 'scholarship coordinator']))
            ->get();
    }

    private function cashierUsers(): EloquentCollection
    {
        return User::with('role')
            ->whereHas('role', fn ($role) => $role->whereRaw('LOWER(name) = ?', ['cashier']))
            ->get();
    }

    private function regionalUsersForRegionCode(string $regionCode): EloquentCollection
    {
        return User::with('profile.agency', 'role')
            ->whereHas('role', fn ($role) => $role->whereRaw('LOWER(name) IN (?, ?)', ['regional staff', 'regional supervisor']))
            ->whereHas('profile.agency', fn ($agency) => $agency->where('region_code', $regionCode))
            ->get();
    }

    private function regionalUsersForAgencyName(string $agencyName): EloquentCollection
    {
        $agencyIds = ListAgencies::whereRaw('LOWER(name) = ?', [Str::lower($agencyName)])
            ->pluck('id');

        return User::with('profile.agency', 'role')
            ->whereHas('role', fn ($role) => $role->whereRaw('LOWER(name) IN (?, ?)', ['regional staff', 'regional supervisor']))
            ->whereHas('profile', fn ($profile) => $profile->whereIn('agency_id', $agencyIds))
            ->get();
    }

    private function scholarName(?Scholars $scholar): string
    {
        return $scholar?->profile?->fullname
            ?? $scholar?->spas_no
            ?? 'A scholar';
    }
}
