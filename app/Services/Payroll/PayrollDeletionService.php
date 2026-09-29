<?php

namespace App\Services\Payroll;

use App\Models\BatchRecipients;
use App\Models\Batches;

class PayrollDeletionService
{
    public function __construct(
        private readonly PayrollActivityService $activities,
        private readonly PayrollStatusService $statuses
    ) {
    }

    public function deleteBatch(Batches $batch): void
    {
        $batch->loadMissing('recipients.stipends', 'recipients.withhelds', 'recipients.allowances');

        foreach ($batch->recipients as $recipient) {
            if ($recipient->scholar_id) {
                $this->statuses->resetScholarTermToApproved($batch, $recipient->scholar_id);
            }

            $this->deleteRecipientFinancialRows($recipient);
            $recipient->delete();
        }

        $batch->logs()->delete();
        $batch->delete();
    }

    public function deleteRecipient(BatchRecipients $recipient): void
    {
        $batch = $recipient->batch;

        if ($batch && $recipient->scholar_id) {
            $this->statuses->returnScholarTermFromPayroll($batch, $recipient->scholar_id);
        }

        $this->deleteRecipientFinancialRows($recipient);

        if ($batch) {
            $this->activities->log(
                $batch,
                'scholar_removed',
                $recipient,
                $recipient->status,
                null,
                'Scholar was removed from a draft/returned payroll.'
            );
        }

        $recipient->delete();
    }

    private function deleteRecipientFinancialRows(BatchRecipients $recipient): void
    {
        $recipient->stipends()->delete();
        $recipient->withhelds()->delete();
        $recipient->allowances()->delete();
    }
}
