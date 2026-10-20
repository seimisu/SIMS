<?php

namespace App\Services\Academic;

use App\Models\Batches;
use App\Models\ScholarTerm;
use App\Models\SchoolCampusCourseCurriculumSubjects;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AcademicRecordEditRules
{
    public function version(ScholarTerm $term): string
    {
        return hash('sha256', json_encode([
            $term->getRawOriginal('updated_at'),
            $term->subjects->sortBy('id')->map(fn ($grade) => collect($grade->getAttributes())->only([
                'id', 'subject_id', 'input_grade', 'grade_id', 'is_incomplete', 'is_drop', 'is_withdrawn', 'remarks',
            ])->sortKeys()->all())->values()->all(),
        ]));
    }

    public function matchingBatches(ScholarTerm $term): Builder
    {
        return Batches::query()
            ->when(! $term->term_id || blank($term->academic_year), fn ($query) => $query->whereRaw('1 = 0'))
            ->whereNull('deleted_at')
            ->where('school_year', $term->academic_year)
            ->whereHas('recipients', fn ($query) => $query->where('scholar_id', $term->scholar_id))
            ->where(function ($query) use ($term) {
                $query->where('term_id', $term->term_id)
                    ->orWhere(function ($legacy) use ($term) {
                        $legacy->whereNull('term_id')
                            ->whereRaw('LOWER(TRIM(academic_term)) = ?', [Str::lower(trim($term->term?->name ?? ''))]);
                    });
            })
            ->orderBy('id');
    }

    public function scholarshipAccess(Collection $batches): array
    {
        if ($batches->isEmpty()) {
            return ['editable' => false, 'reason' => 'No current payroll batch for this academic term.'];
        }
        if ($batches->count() !== 1) {
            return ['editable' => false, 'reason' => 'Multiple current payroll batches require review.'];
        }

        $batch = $batches->first();
        $editable = in_array($batch->status, ['draft', 'rejected_payroll'], true);

        return [
            'editable' => $editable,
            'reason' => $editable ? null : 'Scholarship status can only change while the scholar’s batch is Draft or Returned.',
            'batchName' => $batch->name,
        ];
    }

    public function eligibleSubjects(ScholarTerm $term): Builder
    {
        $schoolInfo = $term->schoolInfo;
        $specializationId = $term->specialization_id ?: $schoolInfo?->specialization_id;

        return SchoolCampusCourseCurriculumSubjects::query()
            ->where('is_active', true)
            ->where('is_delete', false)
            ->where('semester_id', $term->term_id)
            ->where('curriculum_id', $schoolInfo?->curriculum_id ?? 0)
            ->whereHas('curriculum', fn ($query) => $query
                ->where('campus_course_id', $schoolInfo?->campus_course_id ?? 0)
                ->where('is_delete', false))
            ->whereRaw('LOWER(subject_class) = ?', ['academic'])
            ->whereRaw("LOWER(COALESCE(name, '')) NOT LIKE ?", ['%prerequisite%'])
            ->whereRaw("LOWER(COALESCE(name, '')) NOT LIKE ?", ['%pre-requisite%'])
            ->whereRaw("LOWER(COALESCE(subject_code, '')) NOT LIKE ?", ['%prerequisite%'])
            ->whereRaw("LOWER(COALESCE(subject_code, '')) NOT LIKE ?", ['%pre-requisite%'])
            ->where(function ($query) use ($specializationId) {
                $query->whereDoesntHave('specializations')
                    ->when($specializationId, fn ($query) => $query->orWhereHas(
                        'specializations', fn ($specialization) => $specialization->whereKey($specializationId)
                    ));
            });
    }
}
