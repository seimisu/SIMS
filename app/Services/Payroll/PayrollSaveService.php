<?php

namespace App\Services\Payroll;

use App\Models\BatchRecipients;
use App\Models\RecipientAllowance;
use App\Models\RecipientStipend;
use App\Models\RecipientWithheld;
use Vinkla\Hashids\Facades\Hashids;

class PayrollSaveService
{
    public function __construct(private readonly PayrollAllowanceService $allowances)
    {
    }

    public function saveRecipients(int $batchId, array $recipients): bool
    {
        $allowanceTypeIds = $this->allowances->typeIds();
        $changed = false;

        foreach ($recipients as $item) {
            $recipientId = Hashids::decode($item['id'])[0] ?? 0;
            $recipient = BatchRecipients::where('batch_id', $batchId)->findOrFail($recipientId);

            if ($recipient->is_for_removal_from_payroll || $recipient->status === 'for_removal_from_payroll') {
                continue;
            }

            [$totalStipend, $stipendsChanged] = $this->saveStipends($recipient, $item);
            $changed = $stipendsChanged || $changed;
            $totalWithheld = (float) ($item['total_withheld'] ?? 0);
            $learningMaterials = (float) ($item['learning_materials_amount'] ?? 0);
            $clothing = (float) ($item['clothing_amount'] ?? 0);
            $grandTotal = $totalStipend + $totalWithheld + $learningMaterials + $clothing;

            $recipient->fill([
                'total_stipend' => $totalStipend,
                'total_withheld' => $totalWithheld,
                'learning_materials_amount' => $learningMaterials,
                'clothing_amount' => $clothing,
                'grand_total' => $grandTotal,
                'remarks' => $item['remarks'] ?? null,
            ]);

            if ($recipient->isDirty()) {
                $recipient->save();
                $changed = true;
            }

            $changed = $this->saveAllowances($recipient, $item, $allowanceTypeIds, $learningMaterials, $clothing) || $changed;
            $changed = $this->saveWithheld($recipient, $item, $totalWithheld) || $changed;
        }

        return $changed;
    }

    private function saveStipends(BatchRecipients $recipient, array $item): array
    {
        $totalStipend = 0;
        $changed = false;

        foreach (range(1, 5) as $month) {
            $amount = (float) ($item["month_{$month}"] ?? 0);
            $totalStipend += $amount;

            $stipend = RecipientStipend::updateOrCreate(
                [
                    'recipient_id' => $recipient->id,
                    'month_no' => $month,
                ],
                [
                    'month' => 'Month '.$month,
                    'amount' => $amount,
                    'status' => $amount > 0 ? 'pending' : 'withheld',
                ]
            );

            $changed = $stipend->wasRecentlyCreated || $stipend->wasChanged() || $changed;
        }

        return [$totalStipend, $changed];
    }

    private function saveAllowances(
        BatchRecipients $recipient,
        array $item,
        array $allowanceTypeIds,
        float $learningMaterials,
        float $clothing
    ): bool {
        $changed = false;

        foreach ([
            'connectivity' => [
                'classification' => 'connectivity',
                'amount' => $learningMaterials,
            ],
            'clothing' => [
                'classification' => 'clothing',
                'amount' => $clothing,
            ],
        ] as $code => $allowanceData) {
            $amount = $allowanceData['amount'];
            $classification = $allowanceData['classification'];

            if ($amount <= 0) {
                $deleted = RecipientAllowance::where('recipient_id', $recipient->id)
                    ->where(function ($query) use ($classification, $code, $allowanceTypeIds) {
                        $query->where('classification', $classification)
                            ->orWhere('classification', $code);

                        if (! empty($allowanceTypeIds[$code])) {
                            $query->orWhere('allowance_type_id', $allowanceTypeIds[$code]);
                        }
                    })
                    ->delete();

                $changed = $deleted > 0 || $changed;
                continue;
            }

            $allowance = RecipientAllowance::updateOrCreate(
                [
                    'recipient_id' => $recipient->id,
                    'classification' => $classification,
                ],
                [
                    'allowance_type_id' => $allowanceTypeIds[$code] ?? null,
                    'amount' => $amount,
                    'remarks' => $item['remarks'] ?? null,
                    'status' => 'pending',
                ]
            );

            $changed = $allowance->wasRecentlyCreated || $allowance->wasChanged() || $changed;
        }

        return $changed;
    }

    private function saveWithheld(BatchRecipients $recipient, array $item, float $totalWithheld): bool
    {
        if ($totalWithheld > 0) {
            $withheld = RecipientWithheld::updateOrCreate(
                ['recipient_id' => $recipient->id, 'month_no' => null],
                [
                    'total_amount' => $totalWithheld,
                    'remarks' => $item['remarks'] ?? null,
                    'status' => 'pending',
                ]
            );

            return $withheld->wasRecentlyCreated || $withheld->wasChanged();
        }

        return RecipientWithheld::where('recipient_id', $recipient->id)
            ->whereNull('month_no')
            ->delete();
    }
}
