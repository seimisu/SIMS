<?php

namespace App\Services\Payroll;

use App\Models\BatchRecipients;
use App\Models\Batches;
use App\Models\Scholars;

class PayrollRecipientAttachmentService
{
    public function __construct(
        private readonly PayrollAllowanceService $allowances,
        private readonly PayrollBatchService $batches,
        private readonly PayrollScholarEligibilityService $eligibility,
        private readonly PayrollStatusService $statuses
    ) {
    }

    public function attachScholarsToBatch(
        Batches $batch,
        iterable $scholarIds,
        int $batchScholarLimit,
        array $ignoredDuplicateBatchIds = []
    ): array {
        $scholarIds = collect($scholarIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $ignoredDuplicateBatchIds = collect($ignoredDuplicateBatchIds)
            ->push($batch->id)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $latestStatus = $this->statuses->currentBatchStatus($batch);
        $scholars = Scholars::with(['profile:scholar_id,birthdate', 'landbank:scholar_id,account_number'])
            ->whereIn('id', $scholarIds)
            ->get()
            ->keyBy('id');
        $sameTermRecipientScholarIds = BatchRecipients::whereIn('scholar_id', $scholarIds)
            ->whereNotIn('batch_id', $ignoredDuplicateBatchIds)
            ->whereHas('batch', function ($query) use ($batch) {
                $query->where('region', $batch->region)
                    ->where('school_year', $batch->school_year)
                    ->whereNull('deleted_at');

                $batch->term_id
                    ? $query->where('term_id', $batch->term_id)
                    : $query->where('academic_term', $batch->academic_term);
            })
            ->pluck('scholar_id')
            ->map(fn ($id) => (int) $id)
            ->flip();
        $standingsByScholar = $this->statuses->scholarshipStatusesByScholar($batch, $scholarIds);
        $allowanceDefaults = $this->allowances->visibleFixedDefaults();
        $allowanceTypeIds = $this->allowances->typeIds();
        $monthlyLiving = $allowanceDefaults['monthly_living'] ?? 0;
        $addedCount = 0;
        $attachedCount = 0;
        $attachedScholarIds = [];
        $remainingSlots = max(0, $batchScholarLimit - $this->batches->activeRecipientCount($batch));

        foreach ($scholarIds as $scholarId) {
            $scholar = $scholars->get($scholarId);

            if (! $scholar || $sameTermRecipientScholarIds->has($scholar->id)) {
                continue;
            }

            if (
                $remainingSlots <= 0
                && ! BatchRecipients::where('batch_id', $batch->id)
                    ->where('scholar_id', $scholar->id)
                    ->exists()
            ) {
                break;
            }

            $standing = $standingsByScholar->get($scholar->id);
            $recipientMonthlyLiving = $this->eligibility->isPartialAllowanceStanding($standing) ? $monthlyLiving / 2 : $monthlyLiving;
            $totalMonthlyLiving = $recipientMonthlyLiving * 5;
            $recipient = BatchRecipients::firstOrCreate(
                ['batch_id' => $batch->id, 'scholar_id' => $scholar->id],
                [
                    'account_no' => $scholar->landbank?->account_number,
                    'birthday' => $scholar->profile?->birthdate,
                    'period' => trim($batch->academic_term.' AY '.$batch->school_year),
                    'scholarship_status' => $standing,
                    'total_stipend' => $totalMonthlyLiving,
                    'learning_materials_amount' => $allowanceDefaults['connectivity'] ?? 0,
                    'clothing_amount' => $allowanceDefaults['clothing'] ?? 0,
                    'grand_total' => $totalMonthlyLiving + ($allowanceDefaults['connectivity'] ?? 0) + ($allowanceDefaults['clothing'] ?? 0),
                    'status' => 'pending',
                ]
            );

            if ($recipient->wasRecentlyCreated) {
                $addedCount++;
                $remainingSlots--;

                foreach (range(1, 5) as $month) {
                    $recipient->stipends()->create([
                        'month_no' => $month,
                        'month' => 'Month '.$month,
                        'amount' => $recipientMonthlyLiving,
                        'status' => 'pending',
                    ]);
                }

                foreach (['connectivity', 'clothing'] as $code) {
                    $amount = $allowanceDefaults[$code] ?? 0;

                    if ($amount > 0) {
                        $recipient->allowances()->create([
                            'allowance_type_id' => $allowanceTypeIds[$code] ?? null,
                            'classification' => $code,
                            'amount' => $amount,
                            'status' => 'pending',
                        ]);
                    }
                }
            }

            $attachedCount++;
            $attachedScholarIds[] = $scholar->id;
            $this->statuses->updateScholarTermPayrollStatus($batch, $scholar->id, $latestStatus);
        }

        return ['added' => $addedCount, 'attached' => $attachedCount, 'scholar_ids' => $attachedScholarIds];
    }
}
