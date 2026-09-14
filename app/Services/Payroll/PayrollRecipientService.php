<?php

namespace App\Services\Payroll;

use App\Models\BatchRecipients;
use App\Models\Batches;
use App\Support\SystemPermissions;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Vinkla\Hashids\Facades\Hashids;

class PayrollRecipientService
{
    public function __construct(
        private readonly SystemPermissions $permissions,
        private readonly PayrollAllowanceService $allowances,
        private readonly PayrollRevisionService $revisions,
        private readonly PayrollStatusService $statuses
    ) {
    }

    public function liveRows(Batches $batch)
    {
        $recipients = BatchRecipients::with([
            'scholar.profile:scholar_id,fname,mname,lname,suffix,email,birthdate',
            'scholar.program:id,name',
            'scholar.schoolInfo' => fn ($query) => $query
                ->select('id', 'scholar_id', 'campus_id')
                ->latest('id')
                ->with('campus:id,name,generated_name,agency_id'),
            'stipends' => fn ($query) => $query->orderBy('month_no'),
            'withhelds' => fn ($query) => $query->orderBy('month_no'),
            'allowances.allowanceType',
        ])
            ->where('batch_id', $batch->id)
            ->when($this->permissions->shouldScopeToRegion(Auth::user()), function ($query) {
                $query->whereHas('scholar.schoolInfo.campus', function ($campus) {
                    $campus->where('agency_id', Auth::user()?->profile?->agency_id);
                });
            })
            ->orderBy('id')
            ->get();

        $processStandingsByScholar = $this->statuses->scholarshipStatusesByScholar(
            $batch,
            $recipients->pluck('scholar_id')->filter()->unique()
        );

        $forRemovalDetails = $this->revisions->forRemovalDetailsByScholar($batch);
        $shouldShowMovedDetails = $this->permissions->isRegionalRole(Auth::user());

        return $recipients
            ->map(function ($recipient) use ($processStandingsByScholar, $forRemovalDetails, $shouldShowMovedDetails) {
                $learningMaterials = $this->allowances->recipientAmount(
                    $recipient,
                    'connectivity',
                    'connectivity',
                    (float) $recipient->learning_materials_amount
                );
                $clothing = $this->allowances->recipientAmount(
                    $recipient,
                    'clothing',
                    'clothing',
                    (float) $recipient->clothing_amount
                );
                $removalDetails = $forRemovalDetails->get((int) $recipient->scholar_id, []);
                $isMovedNoticeActive = $shouldShowMovedDetails
                    && $recipient->moved_from_batch_id
                    && ! $recipient->moved_notice_cleared_at;

                return [
                    'id' => Hashids::encode($recipient->id),
                    'scholar_id' => Hashids::encode($recipient->scholar_id),
                    'spas_no' => $recipient->scholar?->spas_no,
                    'account_no' => $recipient->account_no,
                    'name' => trim(collect([
                        $recipient->scholar?->profile?->lname,
                        $recipient->scholar?->profile?->fname,
                        $recipient->scholar?->profile?->mname,
                        $recipient->scholar?->profile?->suffix,
                    ])->filter()->join(' ')),
                    'program' => $recipient->scholar?->program?->name,
                    'university' => $recipient->scholar?->schoolInfo->first()?->campus?->generated_name
                        ?? $recipient->scholar?->schoolInfo->first()?->campus?->name,
                    'scholarship_status' => $processStandingsByScholar->get($recipient->scholar_id),
                    'period' => $recipient->period,
                    'months' => collect(range(1, 5))->mapWithKeys(fn ($month) => [
                        "month_{$month}" => (float) ($recipient->stipends->firstWhere('month_no', $month)?->amount ?? 0),
                    ]),
                    'total_withheld' => (float) $recipient->total_withheld,
                    'remarks' => $recipient->remarks,
                    'learning_materials_amount' => (float) $learningMaterials,
                    'clothing_amount' => (float) $clothing,
                    'grand_total' => (float) $recipient->grand_total,
                    'status' => $recipient->status,
                    'is_for_removal' => (bool) $recipient->is_for_removal_from_payroll
                        || $recipient->status === 'for_removal_from_payroll',
                    'for_removal_reason' => $removalDetails['remarks'] ?? null,
                    'for_removal_by' => $removalDetails['created_by'] ?? $recipient->marked_for_removal_by,
                    'for_removal_at' => $removalDetails['created_at'] ?? ($recipient->marked_for_removal_at
                        ? Carbon::parse($recipient->marked_for_removal_at)->format('M d, Y | h:i a')
                        : null),
                    'is_moved_from_returned_payroll' => (bool) $isMovedNoticeActive,
                    'moved_removal_reason' => $isMovedNoticeActive ? $recipient->moved_from_reason : null,
                    'moved_removal_by' => $isMovedNoticeActive ? $recipient->moved_from_marked_by : null,
                    'moved_removal_at' => $isMovedNoticeActive && $recipient->moved_from_marked_at
                        ? Carbon::parse($recipient->moved_from_marked_at)->format('M d, Y | h:i a')
                        : null,
                    'moved_from_batch' => $isMovedNoticeActive ? $recipient->moved_from_batch_name : null,
                ];
            });
    }
}
