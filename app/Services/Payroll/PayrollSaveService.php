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

    public function saveRecipients(int $batchId, array $recipients): void
    {
        $allowanceTypeIds = $this->allowances->typeIds();

        foreach ($recipients as $item) {
            $recipientId = Hashids::decode($item['id'])[0] ?? 0;
            $recipient = BatchRecipients::where('batch_id', $batchId)->findOrFail($recipientId);

            if ($recipient->is_for_removal_from_payroll || $recipient->status === 'for_removal_from_payroll') {
                continue;
            }

            $totalStipend = $this->saveStipends($recipient, $item);
            $totalWithheld = (float) ($item['total_withheld'] ?? 0);
            $learningMaterials = (float) ($item['learning_materials_amount'] ?? 0);
            $clothing = (float) ($item['clothing_amount'] ?? 0);
            $grandTotal = $totalStipend + $totalWithheld + $learningMaterials + $clothing;

            $recipient->update([
                'total_stipend' => $totalStipend,
                'total_withheld' => $totalWithheld,
                'learning_materials_amount' => $learningMaterials,
                'clothing_amount' => $clothing,
                'grand_total' => $grandTotal,
                'remarks' => $item['remarks'] ?? null,
            ]);

            $this->saveAllowances($recipient, $item, $allowanceTypeIds, $learningMaterials, $clothing);
            $this->saveWithheld($recipient, $item, $totalWithheld);
        }
    }

    private function saveStipends(BatchRecipients $recipient, array $item): float
    {
        $totalStipend = 0;

        foreach (range(1, 5) as $month) {
            $amount = (float) ($item["month_{$month}"] ?? 0);
            $totalStipend += $amount;

            RecipientStipend::updateOrCreate(
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
        }

        return $totalStipend;
    }

    private function saveAllowances(
        BatchRecipients $recipient,
        array $item,
        array $allowanceTypeIds,
        float $learningMaterials,
        float $clothing
    ): void {
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
                RecipientAllowance::where('recipient_id', $recipient->id)
                    ->where(function ($query) use ($classification, $code, $allowanceTypeIds) {
                        $query->where('classification', $classification)
                            ->orWhere('classification', $code);

                        if (! empty($allowanceTypeIds[$code])) {
                            $query->orWhere('allowance_type_id', $allowanceTypeIds[$code]);
                        }
                    })
                    ->delete();

                continue;
            }

            RecipientAllowance::updateOrCreate(
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
        }
    }

    private function saveWithheld(BatchRecipients $recipient, array $item, float $totalWithheld): void
    {
        if ($totalWithheld > 0) {
            RecipientWithheld::updateOrCreate(
                ['recipient_id' => $recipient->id, 'month_no' => null],
                [
                    'total_amount' => $totalWithheld,
                    'remarks' => $item['remarks'] ?? null,
                    'status' => 'pending',
                ]
            );

            return;
        }

        RecipientWithheld::where('recipient_id', $recipient->id)
            ->whereNull('month_no')
            ->delete();
    }
}
