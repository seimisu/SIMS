<?php

namespace App\Services\Payroll;

use App\Models\Batches;
use Illuminate\Support\Facades\Schema;

class PayrollStatusTransitionService
{
    public function __construct(
        private readonly PayrollActivityService $activities,
        private readonly PayrollRecipientMovementService $movements,
        private readonly PayrollRecipientService $recipients,
        private readonly PayrollRevisionService $revisions,
        private readonly PayrollStatusService $statuses
    ) {
    }

    public function transition(
        Batches $batch,
        string $newStatus,
        string $oldStatus,
        ?string $remarks,
        ?string $payrollFilePath,
        ?string $payrollFileName,
        ?string $actorName,
        int $batchScholarLimit
    ): int {
        $this->statuses->setBatchStatus($batch, $newStatus);
        $batch->logs()->create($this->batchLogData($newStatus, $remarks, $actorName, $payrollFilePath, $payrollFileName));

        if ($newStatus === 'submitted_payroll') {
            $this->createSubmittedRevision($batch, $payrollFilePath, $payrollFileName, $actorName);
        }

        $this->activities->log(
            $batch,
            match ($newStatus) {
                'submitted_payroll' => 'payroll_submitted',
                'approved_payroll' => 'payroll_approved',
                'rejected_payroll' => 'payroll_returned',
            },
            oldStatus: $oldStatus,
            newStatus: $newStatus,
            remarks: $remarks,
            metadata: array_filter([
                'payroll_file_name' => $payrollFileName,
            ])
        );

        $movedScholarCount = $newStatus === 'rejected_payroll'
            ? $this->movements->moveMarkedRecipientsToNextBatch($batch, $batchScholarLimit)
            : 0;

        $this->statuses->syncBatchRecipientTermStatuses($batch, $newStatus);
        $this->statuses->syncBatchFinancialStatuses($batch, $newStatus);

        return $movedScholarCount;
    }

    private function createSubmittedRevision(Batches $batch, ?string $payrollFilePath, ?string $payrollFileName, ?string $actorName): void
    {
        $rows = $this->recipients->liveRows($batch)
            ->reject(fn ($row) => (bool) ($row['is_for_removal'] ?? false))
            ->values()
            ->all();

        $this->revisions->createSubmitted($batch, $rows, $payrollFilePath, $payrollFileName, $actorName);
    }

    private function batchLogData(
        string $status,
        ?string $remarks,
        ?string $actorName,
        ?string $payrollFilePath,
        ?string $payrollFileName
    ): array {
        $logData = [
            'status' => $status,
            'remarks' => $remarks,
            'action_by' => $actorName,
        ];

        if (
            Schema::hasColumn('batches_logs', 'payroll_file_path')
            && Schema::hasColumn('batches_logs', 'payroll_file_name')
        ) {
            $logData['payroll_file_path'] = $payrollFilePath;
            $logData['payroll_file_name'] = $payrollFileName;
        }

        return $logData;
    }
}
