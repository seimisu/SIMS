<?php

namespace App\Services\Payroll;

use App\Models\Batches;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PayrollStatusService
{
    public function processStatus(string $batchStatus): string
    {
        return match ($batchStatus) {
            'rejected_payroll' => 'REJECTED',
            'submitted_payroll' => 'SUBMITTED',
            'approved_payroll' => 'APPROVED',
            default => 'DRAFT',
        };
    }

    public function currentBatchStatus(?Batches $batch): string
    {
        return $batch?->status ?: 'draft';
    }

    public function setBatchStatus(Batches $batch, string $status): void
    {
        $batch->forceFill(['status' => $status])->save();
    }

    public function scholarshipStatusesByScholar(Batches $batch, iterable $scholarIds)
    {
        $termRows = $this->scholarTermRowsQuery($batch, $scholarIds)
            ->select('id', 'scholar_id')
            ->get();

        if ($termRows->isEmpty()) {
            return collect();
        }

        $processStandings = DB::connection('scholars')
            ->table('scholar_processes')
            ->whereIn('term_record_id', $termRows->pluck('id'))
            ->pluck('scholarship_status', 'term_record_id');

        return $termRows
            ->mapWithKeys(fn ($term) => [
                $term->scholar_id => $processStandings[$term->id] ?? null,
            ])
            ->filter();
    }

    public function updateScholarTermPayrollStatus(Batches $batch, int $scholarId, string $batchStatus): void
    {
        $terms = $this->scholarTermPayrollQuery($batch, $scholarId)
            ->join('scholars', 'scholars.id', '=', 'scholar_term_records.scholar_id')
            ->select('scholar_term_records.id', 'scholars.id as scholar_id')
            ->get();

        if ($terms->isEmpty()) {
            return;
        }

        $this->syncScholarProcessPayrollStatus($terms, $this->processStatus($batchStatus));
    }

    public function resetScholarTermToApproved(Batches $batch, int $scholarId): void
    {
        $terms = $this->scholarTermPayrollQuery($batch, $scholarId)
            ->join('scholars', 'scholars.id', '=', 'scholar_term_records.scholar_id')
            ->select('scholar_term_records.id', 'scholars.id as scholar_id')
            ->get();

        if ($terms->isEmpty()) {
            return;
        }

        $this->syncScholarProcessPayrollStatus($terms, 'NOT SUBMITTED');
    }

    public function returnScholarTermFromPayroll(Batches $batch, int $scholarId): void
    {
        $terms = $this->scholarTermPayrollQuery($batch, $scholarId)
            ->join('scholars', 'scholars.id', '=', 'scholar_term_records.scholar_id')
            ->select('scholar_term_records.id', 'scholars.id as scholar_id')
            ->get();

        if ($terms->isEmpty()) {
            return;
        }

        $this->syncScholarProcessPayrollStatus($terms, 'RETURNED');
    }

    public function syncBatchRecipientTermStatuses(Batches $batch, string $batchStatus): void
    {
        $batch->loadMissing('recipients:id,batch_id,scholar_id,status,is_for_removal_from_payroll');

        foreach ($batch->recipients as $recipient) {
            if ($recipient->is_for_removal_from_payroll || $recipient->status === 'for_removal_from_payroll') {
                continue;
            }

            if ($recipient->scholar_id) {
                $this->updateScholarTermPayrollStatus($batch, $recipient->scholar_id, $batchStatus);
            }
        }
    }

    public function itemStatus(string $batchStatus): string
    {
        return match ($batchStatus) {
            'submitted_payroll' => 'submitted',
            'approved_payroll' => 'approved',
            'rejected_payroll' => 'rejected',
            default => 'pending',
        };
    }

    public function syncBatchFinancialStatuses(Batches $batch, string $batchStatus): void
    {
        $status = $this->itemStatus($batchStatus);

        $batch->loadMissing('recipients.stipends', 'recipients.withhelds', 'recipients.allowances');

        foreach ($batch->recipients as $recipient) {
            if ($recipient->is_for_removal_from_payroll || $recipient->status === 'for_removal_from_payroll') {
                continue;
            }

            $recipient->update(['status' => $status]);
            $recipient->stipends()->update(['status' => $status]);
            $recipient->allowances()->where('amount', '>', 0)->update(['status' => $status]);
            $recipient->withhelds()->where('total_amount', '>', 0)->update(['status' => $status]);
        }
    }

    private function scholarTermPayrollQuery(Batches $batch, int $scholarId)
    {
        return $this->scholarTermRowsQuery($batch, [$scholarId]);
    }

    public function scholarTermRowsQuery(Batches $batch, iterable $scholarIds)
    {
        $ids = collect($scholarIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $query = DB::table('scholar_term_records')
            ->where('academic_year', $batch->school_year);

        if ($ids->isEmpty()) {
            $query->whereRaw('1 = 0');
        } else {
            $query->whereIn('scholar_id', $ids);
        }

        if ($batch->term_id) {
            $query->where('term_id', $batch->term_id);
        } else {
            $query->whereExists(function ($query) use ($batch) {
                $query->select(DB::raw(1))
                    ->from('list_references')
                    ->whereColumn('list_references.id', 'scholar_term_records.term_id')
                    ->whereRaw('LOWER(list_references.name) = ?', [Str::lower($batch->academic_term)]);
            });
        }

        return $query;
    }

    private function syncScholarProcessPayrollStatus(iterable $terms, string $payrollStatus): void
    {
        foreach ($terms as $term) {
            DB::connection('scholars')
                ->table('scholar_processes')
                ->updateOrInsert(
                    ['term_record_id' => $term->id],
                    [
                        'scholar_id' => $term->scholar_id,
                        'payroll' => $payrollStatus,
                        'is_end' => false,
                        'updated_at' => now(),
                        'updated_by' => Auth::user()?->profile?->fullname,
                    ]
                );
        }
    }
}
