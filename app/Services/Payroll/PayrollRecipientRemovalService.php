<?php

namespace App\Services\Payroll;

use App\Models\BatchRecipients;
use App\Models\Batches;

class PayrollRecipientRemovalService
{
    public function __construct(
        private readonly PayrollActivityService $activities,
        private readonly PayrollRevisionService $revisions,
        private readonly PayrollStatusService $statuses
    ) {
    }

    public function markForRemoval(Batches $batch, BatchRecipients $recipient, string $remarks, ?string $actorName): void
    {
        $oldRecipientStatus = $recipient->status;

        $recipient->update([
            'is_for_removal_from_payroll' => true,
            'status' => 'for_removal_from_payroll',
            'marked_for_removal_by' => $actorName,
            'marked_for_removal_at' => now(),
        ]);

        $this->revisions->markRecipientForRemovalInLatest($batch, $recipient, $actorName);

        if ($recipient->scholar_id) {
            $this->statuses->returnScholarTermFromPayroll($batch, $recipient->scholar_id);
        }

        $this->activities->log(
            $batch,
            'scholar_marked_for_removal',
            $recipient,
            $oldRecipientStatus,
            'for_removal_from_payroll',
            $remarks
        );
    }

    public function cancelRemoval(Batches $batch, BatchRecipients $recipient, string $latestStatus): void
    {
        $oldRecipientStatus = $recipient->status;
        $restoredStatus = $this->statuses->itemStatus($latestStatus);

        $recipient->update([
            'is_for_removal_from_payroll' => false,
            'marked_for_removal_by' => null,
            'marked_for_removal_at' => null,
            'status' => $restoredStatus,
        ]);

        $this->revisions->cancelRecipientForRemovalInLatest($batch, $recipient);

        if ($recipient->scholar_id) {
            $this->statuses->updateScholarTermPayrollStatus($batch, $recipient->scholar_id, $latestStatus);
        }

        $this->activities->log(
            $batch,
            'scholar_removal_cancelled',
            $recipient,
            $oldRecipientStatus,
            $restoredStatus,
            'Scholar removal mark was cancelled.'
        );
    }
}
