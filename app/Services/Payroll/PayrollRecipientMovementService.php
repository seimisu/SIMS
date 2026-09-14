<?php

namespace App\Services\Payroll;

use App\Models\BatchRecipients;
use App\Models\Batches;
use Carbon\Carbon;

class PayrollRecipientMovementService
{
    public function __construct(
        private readonly PayrollActivityService $activities,
        private readonly PayrollBatchService $batches,
        private readonly PayrollRecipientAttachmentService $attachments,
        private readonly PayrollRevisionService $revisions
    ) {
    }

    public function moveMarkedRecipientsToNextBatch(Batches $sourceBatch, int $batchScholarLimit): int
    {
        $recipients = BatchRecipients::with(['stipends', 'withhelds', 'allowances'])
            ->where('batch_id', $sourceBatch->id)
            ->where(function ($query) {
                $query->where('is_for_removal_from_payroll', true)
                    ->orWhere('status', 'for_removal_from_payroll');
            })
            ->get();

        if ($recipients->isEmpty()) {
            return 0;
        }

        $scholarIds = $recipients
            ->pluck('scholar_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($scholarIds->isEmpty()) {
            return 0;
        }

        $targetBatch = $this->batches->acceptingBatch(
            $sourceBatch->region,
            $sourceBatch->school_year,
            $sourceBatch->term_id,
            $sourceBatch->academic_term,
            $batchScholarLimit,
            $sourceBatch->id
        ) ?? $this->batches->createAutoBatch(
            $sourceBatch->region,
            $sourceBatch->school_year,
            $sourceBatch->term_id,
            $sourceBatch->academic_term,
            'Payroll batch was auto-created for scholars removed from a returned payroll.'
        );
        $removalDetails = $this->revisions->forRemovalDetailsByScholar($sourceBatch);

        $result = $this->attachments->attachScholarsToBatch($targetBatch, $scholarIds, $batchScholarLimit, [$sourceBatch->id]);
        $attachedScholarIds = collect($result['scholar_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($attachedScholarIds->isEmpty()) {
            return 0;
        }

        $targetRecipientsByScholar = BatchRecipients::where('batch_id', $targetBatch->id)
            ->whereIn('scholar_id', $attachedScholarIds)
            ->get()
            ->keyBy(fn (BatchRecipients $recipient) => (int) $recipient->scholar_id);

        foreach ($recipients as $recipient) {
            if (! $attachedScholarIds->contains((int) $recipient->scholar_id)) {
                continue;
            }

            $targetRecipient = $targetRecipientsByScholar->get((int) $recipient->scholar_id);

            if (! $targetRecipient) {
                continue;
            }

            $details = $removalDetails->get((int) $recipient->scholar_id, []);

            $this->activities->log(
                $sourceBatch,
                'scholar_removed',
                $recipient,
                $recipient->status,
                null,
                $details['remarks'] ?? 'Scholar was removed from the returned payroll and moved to the next accepting payroll batch.',
                ['target_batch_id' => $targetBatch->id, 'target_batch_name' => $targetBatch->name]
            );

            $recipient->stipends()->delete();
            $recipient->withhelds()->delete();
            $recipient->allowances()->delete();
            $recipient->delete();

            $targetRecipient->update([
                'moved_from_batch_id' => $sourceBatch->id,
                'moved_from_batch_name' => $sourceBatch->name,
                'moved_from_reason' => $details['remarks'] ?? null,
                'moved_from_marked_by' => $details['created_by'] ?? null,
                'moved_from_marked_at' => isset($details['raw_created_at'])
                    ? Carbon::parse($details['raw_created_at'])
                    : null,
                'moved_notice_cleared_at' => null,
            ]);

            $this->activities->log(
                $targetBatch,
                'scholar_moved_from_returned_payroll',
                $targetRecipient,
                null,
                $targetRecipient->status,
                $details['remarks'] ?? "Scholar was moved from returned payroll {$sourceBatch->name}.",
                [
                    'source_batch_id' => $sourceBatch->id,
                    'source_batch_name' => $sourceBatch->name,
                    'marked_for_removal_by' => $details['created_by'] ?? null,
                    'marked_for_removal_at' => $details['created_at'] ?? null,
                ]
            );
        }

        return $attachedScholarIds->count();
    }
}
