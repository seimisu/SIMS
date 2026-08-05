<?php

namespace App\Services;

use App\Models\ScholarTerm;
use App\Models\SchoolCampusCourseCurriculumSubjects;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AcademicPerformanceEvaluationService
{
    private const STATUS_CONTINUED = 'GOOD STANDING';
    private const STATUS_PROBATION = 'CONTINUE UNDER PROBATION';
    private const STATUS_PARTIAL = 'CONTINUE WITH PARTIAL ALLOWANCE';
    private const STATUS_TERMINATED = 'TERMINATED WITH SERVICE OBLIGATION';
    private const STATUS_SUBMIT_GRADES = 'CONTINUED';

    public function evaluate(ScholarTerm $term): array
    {
        $term->loadMissing([
            'term:id,name',
            'schoolInfo.course:id,years',
            'subjects.subject:id,curriculum_id,year,unit,subject_class,name,subject_code',
            'subjects.grade:id,grade,is_drop,is_failed,is_incomplete',
        ]);

        $subjects = $term->subjects;
        $academicSubjects = $subjects->filter(fn ($subject) => $this->isAcademicSubject($subject));
        $dominantYear = $this->dominantCurriculumYear($academicSubjects);
        $courseYears = (int) ($term->schoolInfo?->course?->years ?? 0);
        $isGraduating = $courseYears > 0 && $dominantYear !== null && $dominantYear >= $courseYears;
        $deficiencyHistory = $this->deficiencyHistory($term);
        $hasPreviousDeficiency = $deficiencyHistory['has_previous_deficiency'];

        $actualUnits = $academicSubjects->sum(fn ($subject) => $this->unitValue($subject));
        $expectedUnits = $this->expectedUnits($term, $academicSubjects, $dominantYear);
        $lackingUnits = $expectedUnits !== null ? max(0, round($expectedUnits - $actualUnits, 2)) : null;
        $failedCount = $academicSubjects->filter(fn ($subject) => $this->isFailed($subject))->count();
        $totalFailedCount = $deficiencyHistory['previous_failed_count'] + $failedCount;
        $unresolvedFailedCount = $deficiencyHistory['unresolved_failed_count'];
        $incompleteCount = $academicSubjects->filter(fn ($subject) => $this->isIncomplete($subject))->count();
        $droppedUnits = $academicSubjects
            ->filter(fn ($subject) => $this->isDropped($subject))
            ->sum(fn ($subject) => $this->unitValue($subject));
        $isFirstSemester = $this->isFirstSemester($term);

        $recommendedStatus = self::STATUS_CONTINUED;
        $reasons = [];

        if ($dominantYear === null) {
            $reasons[] = 'Curriculum year could not be derived from the submitted subjects.';
        } else {
            $reasons[] = 'Policy group was derived from submitted curriculum subjects: '.$this->yearGroupLabel($dominantYear, $courseYears).'.';
        }

        if ($lackingUnits !== null && $lackingUnits > 0) {
            $reasons[] = "Submitted academic units are {$actualUnits} out of {$expectedUnits}; lacking {$lackingUnits} unit(s).";
        }

        if ($deficiencyHistory['previous_deficiency_count'] > 0 || $deficiencyHistory['previous_status_deficiency']) {
            $reasons[] = 'Previous deficiency history was found.';
        }

        if ($deficiencyHistory['previous_failed_count'] > 0 && $failedCount > 0) {
            $reasons[] = "Failing grade offense count is {$totalFailedCount} including previous term records.";
        }

        if ($deficiencyHistory['unresolved_failed_count'] > 0) {
            $reasons[] = "{$deficiencyHistory['unresolved_failed_count']} previous failing subject(s) appear unresolved.";
        }

        if ($isGraduating) {
            if ($failedCount > 0 || $incompleteCount >= 2) {
                $recommendedStatus = self::STATUS_SUBMIT_GRADES;
                $reasons[] = 'Graduating-year deficiency found, so the policy points to continued submission of grades.';
            }
        } elseif ($dominantYear === 1) {
            [$recommendedStatus, $ruleReasons] = $this->evaluateFirstYear(
                $failedCount,
                $incompleteCount,
                $droppedUnits,
                $lackingUnits,
                $isFirstSemester,
                $deficiencyHistory
            );
            $reasons = [...$reasons, ...$ruleReasons];
        } elseif ($dominantYear === 2) {
            [$recommendedStatus, $ruleReasons] = $this->evaluateSecondYear(
                $failedCount,
                $incompleteCount,
                $lackingUnits,
                $isFirstSemester,
                $deficiencyHistory
            );
            $reasons = [...$reasons, ...$ruleReasons];
        } elseif ($dominantYear !== null) {
            [$recommendedStatus, $ruleReasons] = $this->evaluateUpperYear(
                $failedCount,
                $incompleteCount,
                $droppedUnits,
                $lackingUnits,
                $isFirstSemester,
                $deficiencyHistory
            );
            $reasons = [...$reasons, ...$ruleReasons];
        }

        if ($recommendedStatus === self::STATUS_CONTINUED && count($reasons) === 1) {
            $reasons[] = 'No failed, incomplete, or dropped academic subject triggered a deficiency rule.';
        }

        return [
            'recommended_status' => $recommendedStatus,
            'recommended_status_normalized' => $this->normalizeStatus($recommendedStatus),
            'policy_group' => $dominantYear === null ? 'Manual Review' : $this->yearGroupLabel($dominantYear, $courseYears),
            'manual_review' => $dominantYear === null,
            'reasons' => $reasons,
            'metrics' => [
                'curriculum_year' => $dominantYear,
                'course_years' => $courseYears ?: null,
                'expected_units' => $expectedUnits,
                'actual_units' => $actualUnits,
                'lacking_units' => $lackingUnits,
                'failed_count' => $failedCount,
                'previous_failed_count' => $deficiencyHistory['previous_failed_count'],
                'total_failed_count' => $totalFailedCount,
                'previous_deficiency_count' => $deficiencyHistory['previous_deficiency_count'],
                'resolved_failed_count' => $deficiencyHistory['resolved_failed_count'],
                'unresolved_failed_count' => $unresolvedFailedCount,
                'incomplete_count' => $incompleteCount,
                'dropped_units' => $droppedUnits,
                'has_previous_deficiency' => $hasPreviousDeficiency,
                'term' => $term->term?->name,
            ],
        ];
    }

    private function evaluateFirstYear(int $failedCount, int $incompleteCount, float $droppedUnits, ?float $lackingUnits, bool $isFirstSemester, array $deficiencyHistory): array
    {
        $hasPreviousDeficiency = $deficiencyHistory['has_previous_deficiency'];

        if ($isFirstSemester && $failedCount >= 2) {
            return [self::STATUS_TERMINATED, ['Two failing grades were found at the end of the first semester.']];
        }

        if ($failedCount >= 1 && $incompleteCount >= 1) {
            return [
                $hasPreviousDeficiency ? self::STATUS_PARTIAL : self::STATUS_PROBATION,
                ['A failing grade and an incomplete grade were found in the submitted term.'],
            ];
        }

        if ($lackingUnits !== null && $lackingUnits > 3) {
            return [
                $hasPreviousDeficiency ? self::STATUS_PARTIAL : self::STATUS_PROBATION,
                [$hasPreviousDeficiency ? 'Lacking/underload units exceeded 3 units with previous deficiency history.' : 'Lacking/underload units exceeded 3 units.'],
            ];
        }

        if ($failedCount === 1 || $incompleteCount > 0 || $droppedUnits > 3) {
            return [
                $hasPreviousDeficiency ? self::STATUS_PARTIAL : self::STATUS_PROBATION,
                [$hasPreviousDeficiency ? 'A first-year deficiency was found with previous deficiency history.' : 'A first-year deficiency was found in the submitted term.'],
            ];
        }

        if ($lackingUnits !== null && $lackingUnits > 0) {
            return [self::STATUS_CONTINUED, ['Underload/lacking units did not exceed 3 units.']];
        }

        return [self::STATUS_CONTINUED, []];
    }

    private function evaluateSecondYear(int $failedCount, int $incompleteCount, ?float $lackingUnits, bool $isFirstSemester, array $deficiencyHistory): array
    {
        $hasPreviousDeficiency = $deficiencyHistory['has_previous_deficiency'];
        $totalFailedCount = $deficiencyHistory['previous_failed_count'] + $failedCount;

        if ($isFirstSemester && $failedCount >= 2) {
            return [self::STATUS_TERMINATED, ['Two failing grades were found at the end of the first semester.']];
        }

        if ($failedCount >= 2 || ($failedCount > 0 && $totalFailedCount >= 2)) {
            return [self::STATUS_PARTIAL, ['A second failing grade was found.']];
        }

        if ($lackingUnits !== null && $lackingUnits > 0) {
            return [
                $hasPreviousDeficiency ? self::STATUS_PARTIAL : self::STATUS_PROBATION,
                [$hasPreviousDeficiency ? 'Lacking/underload units were found with previous deficiency history.' : 'Lacking/underload units were found without previous deficiency history.'],
            ];
        }

        if ($failedCount === 1 || $incompleteCount >= 2) {
            return [
                $hasPreviousDeficiency ? self::STATUS_PARTIAL : self::STATUS_PROBATION,
                [$hasPreviousDeficiency ? 'A deficiency was found with previous deficiency history.' : 'A deficiency was found without previous deficiency history.'],
            ];
        }

        return [self::STATUS_CONTINUED, []];
    }

    private function evaluateUpperYear(int $failedCount, int $incompleteCount, float $droppedUnits, ?float $lackingUnits, bool $isFirstSemester, array $deficiencyHistory): array
    {
        $hasPreviousDeficiency = $deficiencyHistory['has_previous_deficiency'];
        $totalFailedCount = $deficiencyHistory['previous_failed_count'] + $failedCount;
        $unresolvedFailedCount = $deficiencyHistory['unresolved_failed_count'];

        if ($isFirstSemester && $failedCount >= 2) {
            return [self::STATUS_TERMINATED, ['Two failing grades were found at the end of the first semester.']];
        }

        if ($failedCount > 0 && $totalFailedCount >= 3 && $unresolvedFailedCount > 0) {
            return [self::STATUS_TERMINATED, ['Third failing grade was found and previous failing grade/s are still unresolved.']];
        }

        if ($failedCount > 0 && $totalFailedCount >= 3) {
            return [self::STATUS_PARTIAL, ['Third failing grade was found, but previous failing grade/s appear to have been passed later.']];
        }

        if ($failedCount >= 2 || ($failedCount > 0 && $totalFailedCount >= 2)) {
            return [self::STATUS_PARTIAL, ['Multiple failing grades were found.']];
        }

        if ($lackingUnits !== null && $lackingUnits > 3) {
            return [
                $hasPreviousDeficiency ? self::STATUS_PARTIAL : self::STATUS_PROBATION,
                [$hasPreviousDeficiency ? 'Lacking/underload units exceeded 3 units with previous deficiency history.' : 'Lacking/underload units exceeded 3 units without previous deficiency history.'],
            ];
        }

        if ($failedCount === 1 || $incompleteCount >= 2 || $droppedUnits > 3) {
            return [
                $hasPreviousDeficiency ? self::STATUS_PARTIAL : self::STATUS_PROBATION,
                [$hasPreviousDeficiency ? 'A deficiency was found with previous deficiency history.' : 'A deficiency was found without previous deficiency history.'],
            ];
        }

        if ($incompleteCount === 1) {
            return [self::STATUS_CONTINUED, ['One incomplete grade was found without a higher-severity deficiency.']];
        }

        return [self::STATUS_CONTINUED, []];
    }

    private function expectedUnits(ScholarTerm $term, Collection $academicSubjects, ?int $dominantYear): ?float
    {
        if ($dominantYear === null || ! $term->term_id) {
            return null;
        }

        $curriculumId = $term->schoolInfo?->curriculum_id
            ?: $academicSubjects
                ->pluck('subject.curriculum_id')
                ->filter()
                ->countBy()
                ->sortDesc()
                ->keys()
                ->first();

        if (! $curriculumId) {
            return null;
        }

        $units = SchoolCampusCourseCurriculumSubjects::query()
            ->where('curriculum_id', $curriculumId)
            ->where('semester_id', $term->term_id)
            ->where('year', (string) $dominantYear)
            ->where('is_active', true)
            ->where('is_delete', false)
            ->whereRaw('LOWER(subject_class) = ?', ['academic'])
            ->whereRaw("LOWER(COALESCE(name, '')) NOT LIKE ?", ['%prerequisite%'])
            ->whereRaw("LOWER(COALESCE(name, '')) NOT LIKE ?", ['%pre-requisite%'])
            ->whereRaw("LOWER(COALESCE(subject_code, '')) NOT LIKE ?", ['%prerequisite%'])
            ->whereRaw("LOWER(COALESCE(subject_code, '')) NOT LIKE ?", ['%pre-requisite%'])
            ->pluck('unit')
            ->filter(fn ($unit) => is_numeric($unit))
            ->sum(fn ($unit) => (float) $unit);

        return $units > 0 ? round($units, 2) : null;
    }

    private function dominantCurriculumYear(Collection $subjects): ?int
    {
        $years = $subjects
            ->groupBy(fn ($subject) => $this->curriculumYear($subject))
            ->filter(fn ($items, $year) => is_numeric($year))
            ->map(fn ($items) => $items->sum(fn ($subject) => $this->unitValue($subject)));

        if ($years->isEmpty()) {
            return null;
        }

        $maxUnits = $years->max();
        $dominantYears = $years->filter(fn ($units) => $units === $maxUnits);

        return $dominantYears->count() === 1 ? (int) $dominantYears->keys()->first() : null;
    }

    private function deficiencyHistory(ScholarTerm $term): array
    {
        $previousTerms = ScholarTerm::with(['subjects.subject:id,name,subject_code', 'subjects.grade'])
            ->where('scholar_id', $term->scholar_id)
            ->whereKeyNot($term->id)
            ->where(function ($query) use ($term) {
                $query->where('created_at', '<', $term->created_at)
                    ->orWhere(function ($query) use ($term) {
                        $query->where('created_at', $term->created_at)
                            ->where('id', '<', $term->id);
                    });
            })
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $previousDeficiencyCount = 0;
        $previousFailedCount = 0;
        $previousIncompleteCount = 0;
        $previousDroppedCount = 0;
        $failedSubjects = [];
        $resolvedFailedSubjects = [];

        foreach ($previousTerms as $previousTerm) {
            $termHasDeficiency = false;

            foreach ($previousTerm->subjects as $subject) {
                $subjectKey = $this->subjectHistoryKey($subject);

                if ($this->isPassed($subject) && $subjectKey && isset($failedSubjects[$subjectKey])) {
                    $resolvedFailedSubjects[$subjectKey] = $failedSubjects[$subjectKey];
                    unset($failedSubjects[$subjectKey]);
                }

                if ($this->isFailed($subject)) {
                    $previousFailedCount++;
                    $termHasDeficiency = true;

                    if ($subjectKey) {
                        $failedSubjects[$subjectKey] = [
                            'subject_id' => $subject->subject_id,
                            'subject' => $subject->subject?->name,
                            'code' => $subject->subject?->subject_code,
                            'term_id' => $previousTerm->id,
                        ];
                    }
                }

                if ($this->isIncomplete($subject)) {
                    $previousIncompleteCount++;
                    $termHasDeficiency = true;
                }

                if ($this->isDropped($subject)) {
                    $previousDroppedCount++;
                    $termHasDeficiency = true;
                }
            }

            if ($termHasDeficiency) {
                $previousDeficiencyCount++;
            }
        }

        $statuses = DB::connection('scholars')
            ->table('scholar_processes')
            ->whereIn('term_record_id', $previousTerms->pluck('id'))
            ->pluck('scholarship_status')
            ->map(fn ($status) => $this->normalizeStatus($status));

        $previousStatusDeficiency = $statuses->contains(fn ($status) => Str::contains($status, ['PROBATION', 'PARTIAL', 'TERMINATED']));
        $hasPreviousDeficiency = $previousDeficiencyCount > 0 || $previousStatusDeficiency;

        return [
            'has_previous_deficiency' => $hasPreviousDeficiency,
            'previous_deficiency_count' => $previousDeficiencyCount,
            'previous_failed_count' => $previousFailedCount,
            'previous_incomplete_count' => $previousIncompleteCount,
            'previous_dropped_count' => $previousDroppedCount,
            'resolved_failed_count' => count($resolvedFailedSubjects),
            'unresolved_failed_count' => count($failedSubjects),
            'unresolved_failed_subjects' => array_values($failedSubjects),
            'previous_status_deficiency' => $previousStatusDeficiency,
        ];
    }

    private function isAcademicSubject($subject): bool
    {
        return Str::lower($subject->subject?->subject_class ?? '') === 'academic';
    }

    private function isDropped($subject): bool
    {
        return (bool) ($subject->grade?->is_drop ?? false);
    }

    private function isFailed($subject): bool
    {
        $grade = Str::upper((string) ($subject->grade?->grade ?? ''));

        return (bool) ($subject->grade?->is_failed ?? false)
            || in_array($grade, ['5', '5.0', '5.00', 'F'], true);
    }

    private function isIncomplete($subject): bool
    {
        $grade = Str::upper((string) ($subject->grade?->grade ?? ''));

        return (bool) ($subject->grade?->is_incomplete ?? false)
            || in_array($grade, ['INC', 'INCOMPLETE', '4', '4.0', '4.00'], true);
    }

    private function isPassed($subject): bool
    {
        return $subject->grade
            && ! $this->isFailed($subject)
            && ! $this->isIncomplete($subject)
            && ! $this->isDropped($subject);
    }

    private function subjectHistoryKey($subject): ?string
    {
        $code = Str::of($subject->subject?->subject_code ?? '')
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', '')
            ->toString();

        if ($code) {
            return 'code:'.$code;
        }

        return $subject->subject_id ? 'id:'.$subject->subject_id : null;
    }

    private function unitValue($subject): float
    {
        return is_numeric($subject->subject?->unit) ? (float) $subject->subject->unit : 0.0;
    }

    private function curriculumYear($subject): ?int
    {
        return is_numeric($subject->subject?->year) ? (int) $subject->subject->year : null;
    }

    private function isFirstSemester(ScholarTerm $term): bool
    {
        return Str::contains(Str::lower($term->term?->name ?? ''), ['first', '1st']);
    }

    private function yearGroupLabel(int $year, int $courseYears): string
    {
        if ($courseYears > 0 && $year >= $courseYears) {
            return 'Graduating Year';
        }

        return match ($year) {
            1 => 'First-Year',
            2 => 'Second-Year',
            default => 'Third/Fourth-Year',
        };
    }

    private function normalizeStatus(?string $status): string
    {
        return Str::of($status ?? '')
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', ' ')
            ->squish()
            ->toString();
    }
}
