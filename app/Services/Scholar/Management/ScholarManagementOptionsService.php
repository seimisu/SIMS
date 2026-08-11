<?php

namespace App\Services\Scholar\Management;

use App\Models\ListPrograms;
use App\Models\ListReferences;
use App\Models\ListStatuses;
use App\Models\Scholars;
use App\Models\ScholarTerm;
use App\Models\SchoolCampusCourseCurriculumSubjects;
use App\Models\SchoolCampusCourses;
use App\Models\SchoolCampuses;
use App\Models\SchoolCampusGrades;
use App\Support\SystemPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Vinkla\Hashids\Facades\Hashids;

class ScholarManagementOptionsService
{
    public function build(Request $request, SystemPermissions $permissions): array
    {
        $user = Auth::user();
        $selectedScholar = $this->selectedScholar($request);
        $academicStatusOptions = $this->academicStatusOptions();
        $monitoring = $this->monitoringOptions($request);

        return [
            ...$this->filters($permissions, $user, $academicStatusOptions),
            ...$monitoring,
            ...$this->detailOptions($request, $permissions, $user, $selectedScholar, $academicStatusOptions),
            'filterSchool' => $this->selectedSchoolFilter($request),
        ];
    }

    private function filters(SystemPermissions $permissions, $user, $academicStatusOptions): array
    {
        return [
            'schoolFilter' => Inertia::optional(
                fn () => Scholars::with([
                    'schoolInfo' => fn ($q) => $q
                        ->select('id', 'scholar_id', 'campus_id')
                        ->with('campus:id,generated_name')
                        ->latest()
                        ->limit(1),
                ])
                    ->when($permissions->shouldScopeToRegion($user), function ($q) use ($permissions, $user) {
                        $q->whereHas('schoolInfo.campus.address', function ($address) use ($permissions, $user) {
                            $address->where('region_code', $permissions->regionCodeFor($user));
                        });
                    })
                    ->get()
                    ->map(function ($q) {
                        $school = $q->schoolInfo->first()?->campus;

                        return [
                            'id' => $school?->id,
                            'name' => $school?->generated_name,
                        ];
                    })
                    ->filter()
                    ->unique('id')
                    ->values()
            ),
            'programFilter' => Inertia::optional(
                fn () => Scholars::with([
                    'program:id,name',
                ])
                    ->when($permissions->shouldScopeToRegion($user), function ($q) use ($permissions, $user) {
                        $q->whereHas('schoolInfo.campus.address', function ($address) use ($permissions, $user) {
                            $address->where('region_code', $permissions->regionCodeFor($user));
                        });
                    })
                    ->get()
                    ->map(fn ($q) => [
                        'id' => $q->program->id,
                        'name' => $q->program->name,
                    ])
                    ->filter()
                    ->unique('id')
                    ->values()
            ),
            'scholarTypeFilter' => Inertia::optional(
                fn () => Scholars::with([
                    'type:id,name',
                ])
                    ->when($permissions->shouldScopeToRegion($user), function ($q) use ($permissions, $user) {
                        $q->whereHas('schoolInfo.campus.address', function ($address) use ($permissions, $user) {
                            $address->where('region_code', $permissions->regionCodeFor($user));
                        });
                    })
                    ->get()
                    ->map(fn ($q) => [
                        'id' => $q->type->id,
                        'name' => $q->type->name,
                    ])
                    ->filter()
                    ->unique('id')
                    ->values()
            ),
            'statusFilter' => Inertia::optional(fn () => $academicStatusOptions),
            'academicStatusOptions' => $academicStatusOptions,
        ];
    }

    private function monitoringOptions(Request $request): array
    {
        $monitoringAcademicYear = $request->input('monitoringAcademicYear');
        $monitoringTerm = $request->input('monitoringTerm');
        $monitoringSubmissionStatus = $request->input('monitoringSubmissionStatus');
        $monitoringAcademicYear = is_array($monitoringAcademicYear) ? ($monitoringAcademicYear['id'] ?? $monitoringAcademicYear['name'] ?? null) : $monitoringAcademicYear;
        $monitoringTermId = is_array($monitoringTerm) ? ($monitoringTerm['id'] ?? null) : $monitoringTerm;
        $monitoringSubmissionStatus = is_array($monitoringSubmissionStatus) ? ($monitoringSubmissionStatus['id'] ?? null) : $monitoringSubmissionStatus;
        $monitoringSubmissionStatus = $monitoringSubmissionStatus ?: 'all';

        $latestMonitoringTerm = ScholarTerm::query()
            ->whereNotNull('academic_year')
            ->whereNotNull('term_id')
            ->orderByDesc('academic_year')
            ->orderByDesc('term_id')
            ->first(['academic_year', 'term_id']);

        $monitoringYearOptions = ScholarTerm::query()
            ->whereNotNull('academic_year')
            ->distinct()
            ->orderByDesc('academic_year')
            ->pluck('academic_year')
            ->map(fn ($year) => [
                'id' => $year,
                'name' => $year,
            ])
            ->values();

        $monitoringTermOptions = ListReferences::where('is_active', true)
            ->where('is_delete', false)
            ->where('type', 'Term')
            ->orderBy('id')
            ->get()
            ->map(fn ($term) => [
                'id' => $term->id,
                'name' => $term->name,
            ]);

        $monitoringAcademicYear ??= $latestMonitoringTerm?->academic_year;
        $monitoringTermId ??= $latestMonitoringTerm?->term_id;

        $submissionStatusOptions = collect(['all', 'submitted', 'approved', 'rejected', 'No Submission'])
            ->map(fn ($status) => [
                'id' => $status,
                'name' => $status === 'all' ? 'All Status' : Str::headline($status),
            ])
            ->values();

        return [
            'monitoringAcademicYear' => $monitoringAcademicYear,
            'monitoringTermId' => $monitoringTermId,
            'monitoringSubmissionStatus' => $monitoringSubmissionStatus,
            'monitoringYearOptions' => $monitoringYearOptions,
            'monitoringTermOptions' => $monitoringTermOptions,
            'submissionStatusOptions' => $submissionStatusOptions,
            'selectedMonitoringYear' => $monitoringAcademicYear
                ? ['id' => $monitoringAcademicYear, 'name' => $monitoringAcademicYear]
                : null,
            'selectedMonitoringTerm' => $monitoringTermId
                ? $monitoringTermOptions->firstWhere('id', (int) $monitoringTermId)
                : null,
            'selectedSubmissionStatus' => $monitoringSubmissionStatus
                ? $submissionStatusOptions->firstWhere('id', $monitoringSubmissionStatus)
                : null,
        ];
    }

    private function detailOptions(Request $request, SystemPermissions $permissions, $user, ?Scholars $selectedScholar, $academicStatusOptions): array
    {
        if (! $selectedScholar) {
            return [
                'statusOptions' => null,
                'programOptions' => null,
                'subProgramOptions' => null,
                'yearOptions' => null,
                'transferCourseOptions' => [],
                'termOptions' => null,
                'subjectOptions' => null,
                'gradeOptions' => null,
                'schoolOptions' => [],
                'courseOptions' => [],
                'generateSubjects' => Inertia::optional(fn () => collect()),
                'standingOptions' => $this->standingOptions(),
            ];
        }

        return [
            'statusOptions' => $academicStatusOptions,
            'programOptions' => $this->programOptions(),
            'subProgramOptions' => $this->subProgramOptions(),
            'yearOptions' => $this->yearOptions(),
            'transferCourseOptions' => $this->courseOptions($request, 'tcampus'),
            'termOptions' => $this->termOptions($selectedScholar),
            'subjectOptions' => $this->subjectOptions($selectedScholar),
            'gradeOptions' => $this->gradeOptions($selectedScholar),
            'schoolOptions' => $this->schoolOptions($permissions, $user),
            'courseOptions' => $this->courseOptions($request, 'campus'),
            'generateSubjects' => $this->generateSubjects($request, $selectedScholar),
            'standingOptions' => $this->standingOptions(),
        ];
    }

    private function academicStatusOptions()
    {
        return ListStatuses::with('color:id,background_color,text_color')
            ->where('type', 'progress')
            ->where('is_active', true)
            ->where('is_delete', false)
            ->orderBy('id')
            ->get()
            ->map(fn ($status) => [
                'id' => Str::upper($status->name),
                'name' => Str::upper($status->name),
                'icon' => $status->icon,
                'bcolor' => $status->color?->background_color,
                'tcolor' => $status->color?->text_color,
            ])
            ->values();
    }

    private function standingOptions()
    {
        return ListStatuses::where('type', 'standing')
            ->where('is_active', true)
            ->where('is_delete', false)
            ->orderBy('id')
            ->get()
            ->map(fn ($status) => [
                'id' => Str::upper($status->name),
                'name' => Str::upper($status->name),
            ])
            ->values();
    }

    private function programOptions()
    {
        return ListPrograms::where('is_active', true)
            ->whereIn('name', ['RA 7687', 'RA 10612', 'MERIT'])
            ->where('is_delete', false)
            ->get()->map(fn ($q) => [
                'id' => $q->id,
                'name' => $q->name,
            ]);
    }

    private function subProgramOptions()
    {
        return ListReferences::where('is_active', true)
            ->where('classification', 'Type')
            ->where('is_delete', false)
            ->get()->map(fn ($q) => [
                'id' => $q->id,
                'name' => $q->name,
            ]);
    }

    private function yearOptions()
    {
        return ListReferences::where('is_active', true)
            ->where('classification', 'Level')
            ->where('is_delete', false)
            ->get()->map(fn ($q) => [
                'id' => $q->id,
                'name' => $q->name,
                'number' => $q->others,
            ]);
    }

    private function termOptions(Scholars $scholar)
    {
        return ListReferences::where('is_active', true)
            ->where('type', 'Term')
            ->where('classification', $scholar->schoolInfo->first()->campus?->term?->name)
            ->where('is_delete', false)
            ->get()->map(fn ($q) => [
                'id' => $q->id,
                'name' => $q->name,
            ]);
    }

    private function subjectOptions(Scholars $scholar)
    {
        return SchoolCampusCourseCurriculumSubjects::where('is_active', true)
            ->where('is_delete', false)
            ->whereRaw('LOWER(subject_class) = ?', ['academic'])
            ->whereRaw("LOWER(COALESCE(name, '')) NOT LIKE ?", ['%prerequisite%'])
            ->whereRaw("LOWER(COALESCE(name, '')) NOT LIKE ?", ['%pre-requisite%'])
            ->whereRaw("LOWER(COALESCE(subject_code, '')) NOT LIKE ?", ['%prerequisite%'])
            ->whereRaw("LOWER(COALESCE(subject_code, '')) NOT LIKE ?", ['%pre-requisite%'])
            ->whereHas('curriculum', function ($q) use ($scholar) {
                $q->where('campus_course_id', $scholar->schoolInfo->first()?->campus_course_id);
            })->get()->map(fn ($q) => [
                'id' => $q->id,
                'name' => Str::upper($q->name),
                'code' => Str::upper($q->subject_code),
                'unit' => $q->unit,
                'class' => $q->subject_class,
            ]);
    }

    private function gradeOptions(Scholars $scholar)
    {
        return SchoolCampusGrades::where('is_active', true)
            ->where('is_delete', false)
            ->where('campus_id', $scholar->schoolInfo->first()?->campus_id)
            ->get()->map(fn ($q) => [
                'id' => $q->id,
                'name' => $q->grade,
                'is_failed' => $q->is_failed,
                'is_incomplete' => $q->is_incomplete,
                'is_drop' => $q->is_drop,
                'is_active' => $q->is_active,
            ]);
    }

    private function schoolOptions(SystemPermissions $permissions, $user)
    {
        return SchoolCampuses::where([
            'is_delete' => false,
            'is_active' => true,
        ])
            ->when($permissions->shouldScopeToRegion($user), function ($q) use ($permissions, $user) {
                $q->whereHas('address', function ($address) use ($permissions, $user) {
                    $address->where('region_code', $permissions->regionCodeFor($user));
                });
            })
            ->get()->map(fn ($campus) => [
                'id' => $campus->id,
                'name' => $campus->generated_name,
            ]);
    }

    private function courseOptions(Request $request, string $campusInput)
    {
        return SchoolCampusCourses::with(['course', 'campus'])->where([
            'is_delete' => false,
            'is_active' => true,
        ])
            ->whereHas(
                'campus',
                fn ($q) => $q->when($request->input($campusInput), function ($q) use ($request, $campusInput) {
                    $q->where('generated_name', 'like', '%'.$request->input($campusInput).'%');
                })
            )
            ->get()
            ->map(fn ($course) => [
                'id' => $course->id,
                'name' => $course->course?->name,
                'campus' => $course->campus?->generated_name,
            ]);
    }

    private function generateSubjects(Request $request, Scholars $scholar)
    {
        return Inertia::optional(
            fn () => SchoolCampusCourseCurriculumSubjects::where('is_active', true)
                ->where('is_delete', false)
                ->whereHas('curriculum', function ($q) use ($scholar) {
                    $q->where('is_active', true)
                        ->where('is_delete', false)
                        ->where('campus_course_id', $scholar->schoolInfo->first()?->campus_course_id);
                })
                ->where('semester_id', $request->input('term'))
                ->where('year', $request->input('year'))
                ->get()->map(fn ($q) => [
                    'id' => $q->id,
                    'name' => $q->name,
                    'code' => $q->subject_code,
                    'unit' => $q->unit,
                ])
        );
    }

    private function selectedScholar(Request $request): ?Scholars
    {
        if (! $request->input('id')) {
            return null;
        }

        return Scholars::find(Hashids::decode($request->input('id'))[0] ?? 0);
    }

    private function selectedSchoolFilter(Request $request)
    {
        if ($request->input('schools') == null) {
            return null;
        }

        return Scholars::with([
            'schoolInfo' => fn ($q) => $q
                ->select('id', 'scholar_id', 'campus_id')
                ->with('campus:id,generated_name')
                ->latest()
                ->limit(1),
        ])
            ->when($request->input('schools'), function ($q, $schools) {
                $q->whereHas('schoolInfo', fn ($w) => $w->whereHas('campus', fn ($r) => $r->whereIn('generated_name', $schools)));
            })
            ->get()
            ->map(function ($q) {
                $school = $q->schoolInfo->first()?->campus;

                return [
                    'id' => $school?->id,
                    'name' => $school?->generated_name,
                ];
            })
            ->filter()
            ->unique('id')
            ->values();
    }
}
