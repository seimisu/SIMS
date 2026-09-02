<?php

namespace App\Services\Payroll;

use App\Models\User;
use App\Support\SystemPermissions;
use Illuminate\Support\Facades\Auth;

class PayrollSignatoryService
{
    public function options()
    {
        $user = Auth::user();
        $permissions = app(SystemPermissions::class);

        return User::query()
            ->where('is_delete', false)
            ->where('is_active', true)
            ->whereHas('profile')
            ->with(['profile.agency'])
            ->when($permissions->shouldScopeToRegion($user), function ($query) use ($user) {
                $query->whereHas('profile', function ($profile) use ($user) {
                    $profile->where('agency_id', $user->profile?->agency_id);
                });
            })
            ->orderBy('email')
            ->get()
            ->map(fn ($signatory) => [
                'id' => $signatory->id,
                'name' => $signatory->profile?->fullname ?? $signatory->email,
                'designation' => $signatory->profile?->designation,
                'agency' => $signatory->profile?->agency?->name,
            ])
            ->values();
    }

    public function find(int|string|null $id): ?array
    {
        if (! $id) {
            return null;
        }

        return $this->options()
            ->firstWhere('id', (int) $id);
    }

    public function findMany(array $ids): array
    {
        $allowedSignatories = $this->options()->keyBy('id');

        return collect($ids)
            ->map(fn ($id) => $allowedSignatories->get((int) $id))
            ->filter()
            ->values()
            ->all();
    }
}
