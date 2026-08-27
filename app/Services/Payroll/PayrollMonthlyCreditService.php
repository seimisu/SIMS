<?php

namespace App\Services\Payroll;

use App\Models\Batches;
use App\Models\PayrollBatchMonthlyCredit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PayrollMonthlyCreditService
{
    public function isCreditEligible(Batches $batch): bool
    {
        return ! (bool) $batch->is_historical
            && strtolower((string) ($batch->source ?? 'system')) === 'system';
    }

    public function ensureForBatch(Batches $batch): Collection
    {
        if (! $this->isCreditEligible($batch)) {
            return collect();
        }

        foreach (range(1, 5) as $month) {
            PayrollBatchMonthlyCredit::firstOrCreate(
                [
                    'batch_id' => $batch->id,
                    'month_no' => $month,
                ],
                [
                    'status' => 'pending',
                    'amount' => 0,
                    'recipient_count' => 0,
                ]
            );
        }

        return $batch->monthlyCredits()
            ->with('creditedBy.profile')
            ->orderBy('month_no')
            ->get();
    }

    public function rowsForBatch(Batches $batch): Collection
    {
        if (! $this->isCreditEligible($batch)) {
            return collect();
        }

        $credits = $this->ensureForBatch($batch)->keyBy('month_no');
        $totals = $this->monthTotals($batch);

        return collect(range(1, 5))->map(function (int $month) use ($credits, $totals) {
            $credit = $credits->get($month);
            $total = $totals->get($month, ['amount' => 0, 'recipient_count' => 0]);

            return [
                'month_no' => $month,
                'label' => "Month {$month}",
                'status' => $credit?->status ?? 'pending',
                'amount' => (float) ($credit?->amount ?: $total['amount']),
                'payroll_amount' => (float) $total['amount'],
                'recipient_count' => (int) ($credit?->recipient_count ?: $total['recipient_count']),
                'credited_by' => $credit?->creditedBy?->profile?->fullname,
                'credited_at' => $credit?->credited_at
                    ? Carbon::parse($credit->credited_at)->format('M d, Y | h:i a')
                    : null,
                'remarks' => $credit?->remarks,
            ];
        });
    }

    public function credit(Batches $batch, int $month, int $userId, ?string $remarks = null): PayrollBatchMonthlyCredit
    {
        if (! $this->isCreditEligible($batch)) {
            abort(422, 'Only system-created payroll batches can be credited.');
        }

        $this->ensureForBatch($batch);

        $total = $this->monthTotals($batch)->get($month, [
            'amount' => 0,
            'recipient_count' => 0,
        ]);

        $credit = PayrollBatchMonthlyCredit::where('batch_id', $batch->id)
            ->where('month_no', $month)
            ->firstOrFail();

        if ($credit->status === 'credited') {
            return $credit;
        }

        $credit->forceFill([
            'status' => 'credited',
            'amount' => $total['amount'],
            'recipient_count' => $total['recipient_count'],
            'credited_by' => $userId,
            'credited_at' => now(),
            'remarks' => $remarks,
        ])->save();

        $this->creditRecipientStipends($batch, $month);

        return $credit;
    }

    private function monthTotals(Batches $batch): Collection
    {
        return DB::table('batch_recipients')
            ->join('recipient_stipends', 'recipient_stipends.recipient_id', '=', 'batch_recipients.id')
            ->where('batch_recipients.batch_id', $batch->id)
            ->whereBetween('recipient_stipends.month_no', [1, 5])
            ->where(function ($query) {
                $query->where('batch_recipients.is_for_removal_from_payroll', false)
                    ->orWhereNull('batch_recipients.is_for_removal_from_payroll');
            })
            ->where('batch_recipients.status', '!=', 'for_removal_from_payroll')
            ->selectRaw('recipient_stipends.month_no, COALESCE(SUM(recipient_stipends.amount), 0) as amount, COUNT(DISTINCT batch_recipients.id) as recipient_count')
            ->groupBy('recipient_stipends.month_no')
            ->get()
            ->keyBy('month_no')
            ->map(fn ($row) => [
                'amount' => (float) $row->amount,
                'recipient_count' => (int) $row->recipient_count,
            ]);
    }

    private function creditRecipientStipends(Batches $batch, int $month): void
    {
        $activeRecipientIds = DB::table('batch_recipients')
            ->where('batch_id', $batch->id)
            ->where(function ($query) {
                $query->where('is_for_removal_from_payroll', false)
                    ->orWhereNull('is_for_removal_from_payroll');
            })
            ->where('status', '!=', 'for_removal_from_payroll')
            ->pluck('id');

        if ($activeRecipientIds->isEmpty()) {
            return;
        }

        DB::table('recipient_stipends')
            ->whereIn('recipient_id', $activeRecipientIds)
            ->where('month_no', $month)
            ->where('amount', '>', 0)
            ->update([
                'status' => 'credited',
                'updated_at' => now(),
            ]);
    }
}
