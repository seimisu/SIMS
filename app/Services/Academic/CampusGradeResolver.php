<?php

namespace App\Services\Academic;

use App\Models\SchoolCampuses;
use App\Models\SchoolCampusGrades;
use App\Support\GradingSystem;
use Illuminate\Validation\ValidationException;

class CampusGradeResolver
{
    public function resolve(
        int $campusId,
        mixed $inputGrade = null,
        bool $isIncomplete = false,
        bool $isDrop = false,
        bool $isWithdrawn = false
    ): ?SchoolCampusGrades {
        $campus = SchoolCampuses::with('grading:id,name')->findOrFail($campusId);

        if (! in_array($campus->grading?->name, GradingSystem::SUPPORTED, true)) {
            throw ValidationException::withMessages([
                'subjects' => ['The campus does not use a supported grading system.'],
            ]);
        }

        if (collect([$isIncomplete, $isDrop, $isWithdrawn])->filter()->count() > 1) {
            throw ValidationException::withMessages([
                'subjects' => ['A subject can only have one special grade classification.'],
            ]);
        }

        $rules = SchoolCampusGrades::where('campus_id', $campusId)
            ->where('is_active', true)
            ->where('is_delete', false)
            ->get();

        if ($isDrop) {
            return $this->firstSpecialRule($rules, 'is_drop', 'dropped');
        }

        if ($isWithdrawn) {
            return $this->firstSpecialRule($rules, 'is_withdrawn', 'withdrawn');
        }

        if ($isIncomplete) {
            return $this->firstSpecialRule($rules, 'is_incomplete', 'incomplete');
        }

        if ($inputGrade === null || $inputGrade === '') {
            return null;
        }

        if (! is_numeric($inputGrade)) {
            throw ValidationException::withMessages([
                'subjects' => ["Grade \"{$inputGrade}\" must be numeric unless marked incomplete, dropped, or withdrawn."],
            ]);
        }

        $gradeValue = (float) $inputGrade;

        if (GradingSystem::isPercent($campus->grading?->name) && ($gradeValue < 0 || $gradeValue > 100)) {
            throw ValidationException::withMessages([
                'subjects' => ["Percentage grade \"{$inputGrade}\" must be between 0 and 100."],
            ]);
        }

        if (GradingSystem::isPercent($campus->grading?->name) && $gradeValue !== (float) (int) $gradeValue) {
            throw ValidationException::withMessages([
                'subjects' => ["Percentage grade \"{$inputGrade}\" must be a whole number."],
            ]);
        }

        $matches = $rules
            ->reject(fn ($rule) => $rule->is_drop || $rule->is_incomplete || $rule->is_withdrawn)
            ->filter(function ($rule) use ($gradeValue) {
                if (! is_numeric($rule->lower) || ! is_numeric($rule->upper)) {
                    return false;
                }

                $lower = (float) $rule->lower;
                $upper = (float) $rule->upper;
                $minimum = min($lower, $upper);
                $maximum = max($lower, $upper);

                return $gradeValue >= $minimum && $gradeValue <= $maximum;
            })
            ->values();

        if ($matches->count() > 1) {
            throw ValidationException::withMessages([
                'subjects' => ["Grade \"{$inputGrade}\" matches multiple campus grading ranges."],
            ]);
        }

        if ($matches->isEmpty()) {
            throw ValidationException::withMessages([
                'subjects' => ["Grade \"{$inputGrade}\" does not match any active grading range for this campus."],
            ]);
        }

        return $matches->first();
    }

    private function firstSpecialRule($rules, string $column, string $label): SchoolCampusGrades
    {
        $matches = $rules->where($column, true)->values();

        if ($matches->isEmpty()) {
            throw ValidationException::withMessages([
                'subjects' => ["No active {$label} grading rule is configured for this campus."],
            ]);
        }

        if ($matches->count() > 1) {
            throw ValidationException::withMessages([
                'subjects' => ["Multiple active {$label} grading rules are configured for this campus."],
            ]);
        }

        return $matches->first();
    }
}
