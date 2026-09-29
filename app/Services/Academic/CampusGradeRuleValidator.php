<?php

namespace App\Services\Academic;

use App\Models\SchoolCampuses;
use App\Support\GradingSystem;
use Illuminate\Validation\ValidationException;

class CampusGradeRuleValidator
{
    public function validate(
        SchoolCampuses $campus,
        array $data,
        ?int $ignoreId = null,
        bool $candidateIsActive = true
    ): void {
        if (! in_array($campus->grading?->name, GradingSystem::SUPPORTED, true)) {
            throw ValidationException::withMessages([
                'grade' => 'This campus does not use a supported grading system.',
            ]);
        }

        $isDrop = (bool) ($data['drop'] ?? false);
        $isIncomplete = (bool) ($data['incomplete'] ?? false);
        $isWithdrawn = (bool) ($data['withdrawn'] ?? false);
        $isFailed = (bool) ($data['fail'] ?? false);
        $specialFlags = [
            'is_drop' => $isDrop,
            'is_incomplete' => $isIncomplete,
            'is_withdrawn' => $isWithdrawn,
        ];

        if (collect([...array_values($specialFlags), $isFailed])->filter()->count() > 1) {
            throw ValidationException::withMessages([
                'grade' => 'Only one grade classification can be enabled.',
            ]);
        }

        $specialColumn = collect($specialFlags)->search(true, true);

        if ($specialColumn !== false) {
            if ($candidateIsActive && $campus->grades()
                ->where('is_active', true)
                ->where('is_delete', false)
                ->where($specialColumn, true)
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->exists()) {
                throw ValidationException::withMessages([
                    'grade' => 'This campus already has an active rule for that special grade classification.',
                ]);
            }

            return;
        }

        if (($data['lower'] ?? null) === null || ($data['upper'] ?? null) === null) {
            throw ValidationException::withMessages([
                'lower' => 'Lower and upper limits are required for passed and failed grade rules.',
            ]);
        }

        $lower = (float) $data['lower'];
        $upper = (float) $data['upper'];

        if ($lower > $upper) {
            throw ValidationException::withMessages([
                'lower' => 'The lower limit must not be greater than the upper limit.',
            ]);
        }

        if (GradingSystem::isPercent($campus->grading?->name)) {
            if ($lower < 0 || $upper > 100) {
                throw ValidationException::withMessages([
                    'lower' => 'Percentage grade ranges must stay between 0 and 100.',
                    'upper' => 'Percentage grade ranges must stay between 0 and 100.',
                ]);
            }

            if ($lower !== (float) (int) $lower || $upper !== (float) (int) $upper) {
                throw ValidationException::withMessages([
                    'lower' => 'Percentage grade ranges must use whole numbers.',
                    'upper' => 'Percentage grade ranges must use whole numbers.',
                ]);
            }
        }

        if (! $candidateIsActive) {
            return;
        }

        $overlap = $campus->grades()
            ->where('is_active', true)
            ->where('is_delete', false)
            ->where('is_drop', false)
            ->where('is_incomplete', false)
            ->where('is_withdrawn', false)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->get()
            ->contains(function ($rule) use ($lower, $upper) {
                if (! is_numeric($rule->lower) || ! is_numeric($rule->upper)) {
                    return false;
                }

                return $lower <= (float) $rule->upper && $upper >= (float) $rule->lower;
            });

        if ($overlap) {
            throw ValidationException::withMessages([
                'lower' => 'This grade range overlaps an existing active campus grading range.',
            ]);
        }
    }
}
