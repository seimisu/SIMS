<?php

namespace App\Services\Payroll;

use App\Models\BatchRecipients;
use App\Models\Batches;
use App\Models\PayrollBatchActivityLog;
use App\Models\PayrollBatchRevision;
use App\Models\PayrollBatchRevisionRecipient;
use App\Support\SystemPermissions;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Vinkla\Hashids\Facades\Hashids;

class PayrollRevisionService
{
    public function __construct(private readonly SystemPermissions $permissions)
    {
    }

    public function latestSubmitted(Batches $batch): ?PayrollBatchRevision
    {
        return PayrollBatchRevision::where('batch_id', $batch->id)
            ->orderByDesc('revision_no')
            ->first();
    }

    public function shouldShowSubmittedSnapshot(string $status): bool
    {
        return $status === 'rejected_payroll'
            && $this->permissions->isScholarshipReviewer(Auth::user())
            && ! $this->permissions->isAdministrator(Auth::user());
    }

    public function createSubmitted(Batches $batch, array $rows, ?string $payrollFilePath, ?string $payrollFileName, ?string $actorName): void
    {
        $revisionNo = ((int) PayrollBatchRevision::where('batch_id', $batch->id)->max('revision_no')) + 1;

        $revision = PayrollBatchRevision::create([
            'batch_id' => $batch->id,
            'revision_no' => $revisionNo,
            'recipients_snapshot' => $rows,
            'totals_snapshot' => $this->totalsSnapshot($rows),
            'payroll_file_path' => $payrollFilePath,
            'payroll_file_name' => $payrollFileName,
            'submitted_by' => $actorName,
            'submitted_at' => now(),
        ]);

        $this->storeRecipientRows($revision, collect($rows));

        BatchRecipients::where('batch_id', $batch->id)
            ->whereNotNull('moved_from_batch_id')
            ->whereNull('moved_notice_cleared_at')
            ->update(['moved_notice_cleared_at' => now()]);
    }

    public function storeRecipientRows(PayrollBatchRevision $revision, $rows): void
    {
        collect($rows)->each(function ($row) use ($revision) {
            $batchRecipientId = Hashids::decode($row['id'] ?? '')[0] ?? null;
            $scholarId = Hashids::decode($row['scholar_id'] ?? '')[0] ?? null;
            $isForRemoval = (bool) ($row['is_for_removal'] ?? false);

            PayrollBatchRevisionRecipient::updateOrCreate(
                [
                    'payroll_batch_revision_id' => $revision->id,
                    'batch_recipient_id' => $batchRecipientId,
                ],
                [
                    'batch_id' => $revision->batch_id,
                    'scholar_id' => $scholarId,
                    'row_payload' => [
                        ...$row,
                        'is_for_removal' => $isForRemoval,
                    ],
                    'is_for_removal' => $isForRemoval,
                ]
            );
        });
    }

    public function ensureRecipientRows(Batches $batch, PayrollBatchRevision $revision): void
    {
        if ($revision->recipients()->exists()) {
            return;
        }

        $forRemovalScholarIds = PayrollBatchActivityLog::where('batch_id', $batch->id)
            ->where('action', 'scholar_marked_for_removal')
            ->whereNotNull('scholar_id')
            ->pluck('scholar_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $rows = collect($revision->recipients_snapshot ?? [])
            ->map(function ($row) use ($forRemovalScholarIds) {
                $rowScholarId = Hashids::decode($row['scholar_id'] ?? '')[0] ?? null;

                if (in_array((int) $rowScholarId, $forRemovalScholarIds, true)) {
                    $row['is_for_removal'] = true;
                }

                return $row;
            });

        $this->storeRecipientRows($revision, $rows);

        $revision->update([
            'recipients_snapshot' => $rows->values()->all(),
            'totals_snapshot' => $this->totalsSnapshot($rows->values()->all()),
        ]);
    }

    public function markRecipientForRemovalInLatest(Batches $batch, BatchRecipients $recipient, ?string $actorName): void
    {
        $revision = $this->latestSubmitted($batch);

        if (! $revision) {
            return;
        }

        $this->ensureRecipientRows($batch, $revision);

        $revision->recipients()
            ->where(function ($query) use ($recipient) {
                $query->where('batch_recipient_id', $recipient->id)
                    ->orWhere('scholar_id', $recipient->scholar_id);
            })
            ->get()
            ->each(function (PayrollBatchRevisionRecipient $snapshot) use ($recipient, $actorName) {
                $row = $snapshot->row_payload ?? [];
                $row['is_for_removal'] = true;

                $snapshot->update([
                    'row_payload' => $row,
                    'is_for_removal' => true,
                    'marked_for_removal_by' => $recipient->marked_for_removal_by ?: $actorName,
                    'marked_for_removal_at' => $recipient->marked_for_removal_at ?: now(),
                ]);
            });

        $rows = $this->submittedRecipients($batch, $revision)->values()->all();

        $revision->update([
            'recipients_snapshot' => $rows,
            'totals_snapshot' => $this->totalsSnapshot($rows),
        ]);
    }

    public function cancelRecipientForRemovalInLatest(Batches $batch, BatchRecipients $recipient): void
    {
        $revision = $this->latestSubmitted($batch);

        if (! $revision) {
            return;
        }

        $this->ensureRecipientRows($batch, $revision);

        $revision->recipients()
            ->where(function ($query) use ($recipient) {
                $query->where('batch_recipient_id', $recipient->id)
                    ->orWhere('scholar_id', $recipient->scholar_id);
            })
            ->get()
            ->each(function (PayrollBatchRevisionRecipient $snapshot) {
                $row = $snapshot->row_payload ?? [];
                $row['is_for_removal'] = false;

                $snapshot->update([
                    'row_payload' => $row,
                    'is_for_removal' => false,
                    'marked_for_removal_by' => null,
                    'marked_for_removal_at' => null,
                ]);
            });

        $rows = $this->submittedRecipients($batch, $revision)->values()->all();

        $revision->update([
            'recipients_snapshot' => $rows,
            'totals_snapshot' => $this->totalsSnapshot($rows),
        ]);
    }

    public function submittedRecipients(Batches $batch, PayrollBatchRevision $revision)
    {
        $this->ensureRecipientRows($batch, $revision);
        $forRemovalDetails = $this->forRemovalDetailsByScholar($batch);

        return $revision->recipients()
            ->orderBy('id')
            ->get()
            ->map(function (PayrollBatchRevisionRecipient $snapshot) use ($forRemovalDetails) {
                $details = $forRemovalDetails->get((int) $snapshot->scholar_id, []);

                return [
                    ...($snapshot->row_payload ?? []),
                    'is_for_removal' => (bool) $snapshot->is_for_removal,
                    'for_removal_reason' => $details['remarks'] ?? null,
                    'for_removal_by' => $details['created_by'] ?? $snapshot->marked_for_removal_by,
                    'for_removal_at' => $details['created_at'] ?? ($snapshot->marked_for_removal_at
                        ? Carbon::parse($snapshot->marked_for_removal_at)->format('M d, Y | h:i a')
                        : null),
                ];
            });
    }

    public function forRemovalDetailsByScholar(Batches $batch)
    {
        return PayrollBatchActivityLog::where('batch_id', $batch->id)
            ->where('action', 'scholar_marked_for_removal')
            ->whereNotNull('scholar_id')
            ->orderByDesc('created_at')
            ->get(['scholar_id', 'remarks', 'created_by', 'created_at'])
            ->unique('scholar_id')
            ->mapWithKeys(fn ($log) => [
                (int) $log->scholar_id => [
                    'remarks' => $log->remarks,
                    'created_by' => $log->created_by,
                    'created_at' => $log->created_at
                        ? Carbon::parse($log->created_at)->format('M d, Y | h:i a')
                        : null,
                    'raw_created_at' => $log->created_at,
                ],
            ]);
    }

    public function movedFromReturnedPayrollDetailsByScholar(Batches $batch)
    {
        $latestSubmittedAt = PayrollBatchRevision::where('batch_id', $batch->id)
            ->max('submitted_at');

        return PayrollBatchActivityLog::where('batch_id', $batch->id)
            ->where('action', 'scholar_moved_from_returned_payroll')
            ->whereNotNull('scholar_id')
            ->when($latestSubmittedAt, fn ($query) => $query->where('created_at', '>', $latestSubmittedAt))
            ->orderByDesc('created_at')
            ->get(['scholar_id', 'remarks', 'created_by', 'created_at', 'metadata'])
            ->unique('scholar_id')
            ->mapWithKeys(fn ($log) => [
                (int) $log->scholar_id => [
                    'remarks' => $log->remarks,
                    'created_by' => $log->created_by,
                    'created_at' => $log->created_at
                        ? Carbon::parse($log->created_at)->format('M d, Y | h:i a')
                        : null,
                    'source_batch_name' => $log->metadata['source_batch_name'] ?? null,
                ],
            ]);
    }

    public function totalsSnapshot(array $rows): array
    {
        return collect($rows)
            ->reject(fn ($row) => (bool) ($row['is_for_removal'] ?? false))
            ->reduce(function ($totals, $row) {
                foreach (range(1, 5) as $month) {
                    $totals["month_{$month}"] += (float) ($row['months']["month_{$month}"] ?? 0);
                }

                $totals['total_withheld'] += (float) ($row['total_withheld'] ?? 0);
                $totals['learning_materials_amount'] += (float) ($row['learning_materials_amount'] ?? 0);
                $totals['clothing_amount'] += (float) ($row['clothing_amount'] ?? 0);
                $totals['grand_total'] += (float) ($row['grand_total'] ?? 0);

                return $totals;
            }, [
                'month_1' => 0,
                'month_2' => 0,
                'month_3' => 0,
                'month_4' => 0,
                'month_5' => 0,
                'total_withheld' => 0,
                'learning_materials_amount' => 0,
                'clothing_amount' => 0,
                'grand_total' => 0,
            ]);
    }
}
