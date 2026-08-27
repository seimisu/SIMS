<?php

namespace App\Services\Payroll;

use App\Models\Batches;
use App\Services\Notifications\RoleBellNotificationService;

class PayrollNotificationService
{
    public function __construct(private readonly RoleBellNotificationService $notifications)
    {
    }

    public function sendStatusChange(Batches $batch, string $status): void
    {
        $batchName = $batch->name ?: 'Payroll batch #'.$batch->id;

        if ($status === 'submitted_payroll') {
            $this->notifications->notifyScholarshipStaff(
                'payroll_submitted',
                'Payroll submitted',
                "{$batchName} was submitted for review.",
                '/stipends',
                'batches',
                $batch->id
            );
        }

        if ($status === 'approved_payroll') {
            $this->notifications->notifyRegionalPayrollResult(
                (string) $batch->region,
                'payroll_approved',
                'Payroll approved',
                "{$batchName} was approved.",
                '/stipends',
                'batches',
                $batch->id
            );

            $this->notifications->notifyCashiers(
                'payroll_ready_for_crediting',
                'Payroll ready for crediting',
                "{$batchName} was approved and is ready for monthly crediting.",
                '/cashier/credits',
                'batches',
                $batch->id
            );
        }

        if ($status === 'rejected_payroll') {
            $this->notifications->notifyRegionalPayrollResult(
                (string) $batch->region,
                'payroll_returned',
                'Payroll returned',
                "{$batchName} was returned for revision.",
                '/stipends',
                'batches',
                $batch->id
            );
        }
    }
}
