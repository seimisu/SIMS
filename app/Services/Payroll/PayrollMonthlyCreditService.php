<?php

namespace App\Services\Payroll;

use App\Models\Batches;
use App\Models\PayrollBatchMonthlyCredit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;

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
                'recipient_count' => (int) ($credit?->recipient_count ?? 0),
                'credited_by' => $credit?->creditedBy?->profile?->fullname,
                'credited_at' => $credit?->credited_at
                    ? Carbon::parse($credit->credited_at)->format('M d, Y | h:i a')
                    : null,
                'remarks' => $credit?->remarks,
                'progress_label' => "{$credit?->recipient_count}/{$total['recipient_count']}",
            ];
        });
    }

    public function recipientRowsForMonth(Batches $batch, int $month): Collection
    {
        return $this->eligibleStipendsQuery($batch, $month)
            ->with([
                'recipient.scholar.profile:scholar_id,fname,mname,lname,suffix',
                'creditedBy.profile',
            ])
            ->orderBy('recipient_id')
            ->get()
            ->map(function ($stipend) {
                $profile = $stipend->recipient?->scholar?->profile;

                return [
                    'id' => Hashids::encode($stipend->id),
                    'name' => trim(collect([
                        $profile?->lname,
                        $profile?->fname,
                        $profile?->mname,
                        $profile?->suffix,
                    ])->filter()->join(' ')),
                    'spas_no' => $stipend->recipient?->scholar?->spas_no,
                    'account_no' => $stipend->recipient?->account_no,
                    'amount' => (float) $stipend->amount,
                    'status' => $stipend->status === 'credited' ? 'credited' : 'pending',
                    'remarks' => $stipend->remarks,
                    'credited_by' => $stipend->creditedBy?->profile?->fullname,
                    'credited_at' => $stipend->credited_at
                        ? Carbon::parse($stipend->credited_at)->format('M d, Y | h:i a')
                        : null,
                ];
            });
    }

    public function creditSelected(
        Batches $batch,
        int $month,
        int $userId,
        array $recipientDecisions
    ): PayrollBatchMonthlyCredit {
        if (! $this->isCreditEligible($batch)) {
            abort(422, 'Only system-created payroll batches can be marked as deposit.');
        }

        $this->ensureForBatch($batch);
        $submitted = collect($recipientDecisions)->keyBy('id');
        $pending = $this->eligibleStipendsQuery($batch, $month)
            ->where(function ($query) {
                $query->where('recipient_stipends.status', '!=', 'credited')
                    ->orWhereNull('recipient_stipends.status');
            })
            ->lockForUpdate()
            ->get();
        $pendingIds = $pending->mapWithKeys(fn ($stipend) => [Hashids::encode($stipend->id) => $stipend->id]);

        if ($pendingIds->keys()->sort()->values()->all() !== $submitted->keys()->sort()->values()->all()) {
            abort(409, 'The recipient deposit list changed. Reload the month and try again.');
        }

        $selectedIds = $submitted
            ->filter(fn ($decision) => (bool) ($decision['selected'] ?? false))
            ->keys();

        if ($selectedIds->isEmpty()) {
            abort(422, 'Select at least one scholar to deposit.');
        }

        foreach ($pending as $stipend) {
            $hash = Hashids::encode($stipend->id);
            $decision = $submitted->get($hash);

            if ((bool) $decision['selected']) {
                $stipend->forceFill([
                    'status' => 'credited',
                    'remarks' => null,
                    'credited_by' => $userId,
                    'credited_at' => now(),
                ])->save();
            } else {
                $stipend->forceFill(['remarks' => trim((string) $decision['remarks'])])->save();
            }
        }

        return $this->refreshSummary($batch, $month, $userId);
    }

    public function credit(Batches $batch, int $month, int $userId, ?string $remarks = null): PayrollBatchMonthlyCredit
    {
        if (! $this->isCreditEligible($batch)) {
            abort(422, 'Only system-created payroll batches can be marked as deposit.');
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
            ->where('recipient_stipends.amount', '>', 0)
            ->selectRaw('recipient_stipends.month_no, COALESCE(SUM(recipient_stipends.amount), 0) as amount, COUNT(DISTINCT batch_recipients.id) as recipient_count')
            ->groupBy('recipient_stipends.month_no')
            ->get()
            ->keyBy('month_no')
            ->map(fn ($row) => [
                'amount' => (float) $row->amount,
                'recipient_count' => (int) $row->recipient_count,
            ]);
    }

    private function eligibleStipendsQuery(Batches $batch, int $month)
    {
        return \App\Models\RecipientStipend::query()
            ->where('month_no', $month)
            ->where('amount', '>', 0)
            ->whereHas('recipient', function ($query) use ($batch) {
                $query->where('batch_id', $batch->id)
                    ->where(function ($query) {
                        $query->where('is_for_removal_from_payroll', false)
                            ->orWhereNull('is_for_removal_from_payroll');
                    })
                    ->where('status', '!=', 'for_removal_from_payroll');
            });
    }

    private function refreshSummary(Batches $batch, int $month, int $userId): PayrollBatchMonthlyCredit
    {
        $rows = $this->eligibleStipendsQuery($batch, $month)->get();
        $credited = $rows->where('status', 'credited');
        $totalCount = $rows->count();
        $creditedCount = $credited->count();
        $status = $creditedCount === 0
            ? 'pending'
            : ($creditedCount >= $totalCount ? 'credited' : 'partial');

        $credit = PayrollBatchMonthlyCredit::where('batch_id', $batch->id)
            ->where('month_no', $month)
            ->lockForUpdate()
            ->firstOrFail();

        $credit->forceFill([
            'status' => $status,
            'amount' => $credited->sum(fn ($stipend) => (float) $stipend->amount),
            'recipient_count' => $creditedCount,
            'credited_by' => $userId,
            'credited_at' => $creditedCount > 0 ? now() : null,
        ])->save();

        return $credit->refresh();
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
