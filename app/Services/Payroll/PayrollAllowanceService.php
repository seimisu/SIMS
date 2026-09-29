<?php

namespace App\Services\Payroll;

use App\Models\AllowanceType;
use App\Models\BatchRecipients;
use Illuminate\Validation\ValidationException;

class PayrollAllowanceService
{
    public function typeIds(): array
    {
        return AllowanceType::whereIn('code', [
            'connectivity',
            'clothing',
        ])
            ->pluck('id', 'code')
            ->all();
    }

    public function visibleFixedDefaults(): array
    {
        return AllowanceType::whereIn('code', [
            'monthly_living',
            'connectivity',
            'clothing',
        ])
            ->where('is_variable', false)
            ->where('is_active', true)
            ->pluck('default_amount', 'code')
            ->map(fn ($amount) => (float) $amount)
            ->all();
    }

    public function recipientAmount(BatchRecipients $recipient, string $code, string $legacyClassification, float $fallback): float
    {
        $allowance = $recipient->allowances->first(function ($allowance) use ($code, $legacyClassification) {
            return $allowance->allowanceType?->code === $code
                || $allowance->classification === $legacyClassification
                || $allowance->classification === $code;
        });

        return (float) ($allowance?->amount ?? $fallback);
    }

    public function metadata(array $codes)
    {
        return AllowanceType::whereIn('code', $codes)
            ->where('is_active', true)
            ->get()
            ->sortBy(fn ($allowance) => array_search($allowance->code, $codes, true))
            ->values()
            ->map(fn ($allowance) => [
                'code' => $allowance->code,
                'name' => $allowance->name,
                'default_amount' => (float) ($allowance->default_amount ?? 0),
                'max_amount' => $allowance->max_amount !== null ? (float) $allowance->max_amount : null,
                'is_variable' => (bool) $allowance->is_variable,
            ]);
    }

    public function limits()
    {
        return $this->metadata(['connectivity', 'clothing'])
            ->keyBy('code');
    }

    public function enforceMaximums(array $recipients): void
    {
        $maxAmounts = AllowanceType::whereIn('code', ['connectivity', 'clothing'])
            ->whereNotNull('max_amount')
            ->pluck('max_amount', 'code')
            ->map(fn ($amount) => (float) $amount);

        if ($maxAmounts->isEmpty()) {
            return;
        }

        $fieldCodes = [
            'learning_materials_amount' => 'connectivity',
            'clothing_amount' => 'clothing',
        ];
        $errors = [];

        foreach ($recipients as $index => $recipient) {
            foreach ($fieldCodes as $field => $code) {
                if (! $maxAmounts->has($code)) {
                    continue;
                }

                $amount = (float) ($recipient[$field] ?? 0);
                $maxAmount = $maxAmounts[$code];

                if ($amount > $maxAmount) {
                    $errors["recipients.{$index}.{$field}"] = "The amount must not exceed {$maxAmount}.";
                }
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }
}
