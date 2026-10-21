<?php

namespace App\Services\Payroll;

use App\Models\Batches;
use App\Models\RecipientAllowance;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Vinkla\Hashids\Facades\Hashids;

class PayrollAllowanceCreditService
{
    public function summaryForBatch(Batches $batch): array
    {
        $summary = $this->eligibleAllowancesQuery($batch)
            ->selectRaw("
                COUNT(*) as total_count,
                COALESCE(SUM(amount), 0) as total_amount,
                SUM(CASE WHEN deposit_status = 'credited' THEN 1 ELSE 0 END) as credited_count,
                COALESCE(SUM(CASE WHEN deposit_status = 'credited' THEN amount ELSE 0 END), 0) as credited_amount
            ")
            ->first();

        return [
            'total_count' => (int) $summary->total_count,
            'total_amount' => (float) $summary->total_amount,
            'credited_count' => (int) $summary->credited_count,
            'credited_amount' => (float) $summary->credited_amount,
            'pending_count' => (int) $summary->total_count - (int) $summary->credited_count,
            'status' => (int) $summary->total_count === 0
                ? 'pending'
                : ((int) $summary->credited_count === 0
                    ? 'pending'
                    : ((int) $summary->credited_count >= (int) $summary->total_count ? 'credited' : 'partial')),
        ];
    }

    public function recipientRowsForBatch(Batches $batch): Collection
    {
        return $this->eligibleAllowancesQuery($batch)
            ->with([
                'recipient.scholar.profile:scholar_id,fname,mname,lname,suffix',
                'allowanceType',
                'depositedBy.profile',
            ])
            ->orderBy('recipient_id')
            ->orderBy('id')
            ->get()
            ->map(function (RecipientAllowance $allowance) {
                $profile = $allowance->recipient?->scholar?->profile;
                $name = trim(collect([
                    $profile?->lname,
                    $profile?->fname,
                    $profile?->mname,
                    $profile?->suffix,
                ])->filter()->join(' '));

                return [
                    'id' => Hashids::encode($allowance->id),
                    'name' => $name,
                    'spas_no' => $allowance->recipient?->scholar?->spas_no,
                    'account_no' => $allowance->recipient?->account_no,
                    'allowance' => $allowance->allowanceType?->name
                        ?? Str::headline(str_replace('_', ' ', $allowance->classification)),
                    'amount' => (float) $allowance->amount,
                    'status' => $allowance->deposit_status === 'credited' ? 'credited' : 'pending',
                    'remarks' => $allowance->deposit_remarks,
                    'deposited_by' => $allowance->depositedBy?->profile?->fullname,
                    'deposited_at' => $allowance->deposited_at
                        ? Carbon::parse($allowance->deposited_at)->format('M d, Y | h:i a')
                        : null,
                ];
            });
    }

    public function creditSelected(Batches $batch, int $userId, array $decisions): array
    {
        $submitted = collect($decisions)->keyBy('id');
        $pending = $this->eligibleAllowancesQuery($batch)
            ->where(function ($query) {
                $query->where('deposit_status', '!=', 'credited')
                    ->orWhereNull('deposit_status');
            })
            ->lockForUpdate()
            ->get();
        $pendingIds = $pending->mapWithKeys(fn (RecipientAllowance $allowance) => [
            Hashids::encode($allowance->id) => $allowance->id,
        ]);

        if ($pendingIds->keys()->sort()->values()->all() !== $submitted->keys()->sort()->values()->all()) {
            abort(409, 'The allowance deposit list changed. Reload the batch and try again.');
        }

        $selected = $submitted->filter(fn ($decision) => (bool) ($decision['selected'] ?? false));

        if ($selected->isEmpty()) {
            abort(422, 'Select at least one allowance to deposit.');
        }

        foreach ($pending as $allowance) {
            $decision = $submitted->get(Hashids::encode($allowance->id));

            if ((bool) $decision['selected']) {
                $allowance->forceFill([
                    'deposit_status' => 'credited',
                    'deposit_remarks' => null,
                    'deposited_by' => $userId,
                    'deposited_at' => now(),
                ])->save();
            } else {
                $allowance->forceFill([
                    'deposit_remarks' => trim((string) $decision['remarks']),
                ])->save();
            }
        }

        $credited = $pending->filter(fn (RecipientAllowance $allowance) => $selected->has(Hashids::encode($allowance->id)));

        return [
            'recipient_count' => $credited->count(),
            'amount' => (float) $credited->sum(fn (RecipientAllowance $allowance) => (float) $allowance->amount),
            'summary' => $this->summaryForBatch($batch),
        ];
    }

    private function eligibleAllowancesQuery(Batches $batch)
    {
        return RecipientAllowance::query()
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
}
