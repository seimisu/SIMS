<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Mail\activationLinkMail;
use App\Models\ActivityLogs;
use App\Models\ListPrograms;
use App\Models\ListReferences;
use App\Models\ListStatuses;
use App\Models\LocationBarangays;
use App\Models\LocationCity;
use App\Models\LocationProvinces;
use App\Models\LocationRegions;
use App\Models\Scholars;
use App\Models\ScholarTerm;
use App\Models\SchoolCampusCourseCurriculumSubjects;
use App\Models\SchoolCampusCourses;
use App\Models\SchoolCampuses;
use App\Models\SchoolCampusGrades;
use App\Models\StudentDocument;
use App\References\LocationClass;
use App\Support\SystemPermissions;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Vinkla\Hashids\Facades\Hashids;

class Scholar1Controller extends Controller
{
    private function academicStatusMeta(?string $status, ?array $statusOption = null): array
    {
        $status = Str::upper($status ?: 'NEW');

        return [
            'id' => $status,
            'name' => $status,
            'bcolor' => $statusOption['bcolor'] ?? 'bg-slate-100',
            'tcolor' => $statusOption['tcolor'] ?? 'text-slate-600',
            'icon' => $statusOption['icon'] ?? 'IconProgressCheck',
        ];
    }

    private function submissionStatusMeta(?string $status): array
    {
        $status = $status ?: 'No Submission';

        return [
            'id' => $status,
            'name' => Str::headline($status),
            'bcolor' => match ($status) {
                'submitted' => 'bg-blue-100',
                'approved' => 'bg-green-100',
                'rejected' => 'bg-red-100',
                'draft' => 'bg-slate-100',
                default => 'bg-gray-100',
            },
            'tcolor' => match ($status) {
                'submitted' => 'text-blue-600',
                'approved' => 'text-green-600',
                'rejected' => 'text-red-600',
                'draft' => 'text-slate-600',
                default => 'text-gray-500',
            },
            'icon' => match ($status) {
                'submitted' => 'IconUpload',
                'approved' => 'IconCircleCheck',
                'rejected' => 'IconCircleX',
                'draft' => 'IconEdit',
                default => 'IconCircleDashed',
            },
        ];
    }

    private function academicYearSortValue(?string $academicYear): int
    {
        if (preg_match('/\d{4}/', $academicYear ?? '', $matches)) {
            return (int) $matches[0];
        }

        return 0;

    }

    private function termSortValue(?string $term): int
    {
        $term = Str::lower($term ?? '');

        return match (true) {
            Str::contains($term, ['4th', 'fourth']) => 4,
            Str::contains($term, ['3rd', 'third']) => 3,
            Str::contains($term, ['2nd', 'second']) => 2,
            Str::contains($term, ['1st', 'first']) => 1,
            default => 0,
        };
    }

    public function index(Request $request, LocationClass $location)
    {
        $permissions = app(SystemPermissions::class);
        $user = Auth::user();

        $schoolFilter = Inertia::optional(
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
        );
        $programFilter = Inertia::optional(
            fn () => Scholars::with([
                'program:id,name',
            ])
                ->when($permissions->shouldScopeToRegion($user), function ($q) use ($permissions, $user) {
                    $q->whereHas('schoolInfo.campus.address', function ($address) use ($permissions, $user) {
                        $address->where('region_code', $permissions->regionCodeFor($user));
                    });
                })
                ->get()
                ->map(function ($q) {

                    return [
                        'id' => $q->program->id,
                        'name' => $q->program->name,
                    ];
                })
                ->filter()
                ->unique('id')
                ->values()
        );
        $scholarTypeFilter = Inertia::optional(
            fn () => Scholars::with([
                'type:id,name',
            ])
                ->when($permissions->shouldScopeToRegion($user), function ($q) use ($permissions, $user) {
                    $q->whereHas('schoolInfo.campus.address', function ($address) use ($permissions, $user) {
                        $address->where('region_code', $permissions->regionCodeFor($user));
                    });
                })
                ->get()
                ->map(function ($q) {
                    return [
                        'id' => $q->type->id,
                        'name' => $q->type->name,
                    ];
                })
                ->filter()
                ->unique('id')
                ->values()
        );

        $academicStatusOptions = ListStatuses::with('color:id,background_color,text_color')
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

        $statusFilter = Inertia::optional(fn () => $academicStatusOptions);
        $statusOptions = $request->input('id') ? $academicStatusOptions : null;
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

        $selectedMonitoringYear = $monitoringAcademicYear
            ? ['id' => $monitoringAcademicYear, 'name' => $monitoringAcademicYear]
            : null;

        $selectedMonitoringTerm = $monitoringTermId
            ? $monitoringTermOptions->firstWhere('id', (int) $monitoringTermId)
            : null;

        $submissionStatusOptions = collect(['all', 'submitted', 'approved', 'rejected', 'No Submission'])
            ->map(fn ($status) => [
                'id' => $status,
                'name' => $status === 'all' ? 'All Status' : Str::headline($status),
            ])
            ->values();

        $standingOptions = ListStatuses::where('type', 'standing')
            ->where('is_active', true)
            ->where('is_delete', false)
            ->orderBy('id')
            ->get()
            ->map(fn ($status) => [
                'id' => Str::upper($status->name),
                'name' => Str::upper($status->name),
            ])
            ->values();

        $programOptions = $request->input('id') ? ListPrograms::where('is_active', true)
            ->whereIn('name', ['RA 7687', 'RA 10612', 'MERIT'])
            ->where('is_delete', false)
            ->get()->map(function ($q) {
                return [
                    'id' => $q->id,
                    'name' => $q->name,
                ];
            }) : null;

        $subProgramOptions = $request->input('id') ? ListReferences::where('is_active', true)
            ->where('classification', 'Type')
            ->where('is_delete', false)
            ->get()->map(function ($q) {
                return [
                    'id' => $q->id,
                    'name' => $q->name,
                ];
            }) : null;

        $yearOptions = $request->input('id') ? ListReferences::where('is_active', true)
            ->where('classification', 'Level')
            ->where('is_delete', false)
            ->get()->map(function ($q) {
                return [
                    'id' => $q->id,
                    'name' => $q->name,
                    'number' => $q->others,
                ];
            }) : null;

        $termOptions = $request->input('id') ? ListReferences::where('is_active', true)
            ->where('type', 'Term')
            ->where('classification', Scholars::find(Hashids::decode($request->input('id'))[0] ?? 0)->schoolInfo->first()->campus?->term?->name)
            ->where('is_delete', false)
            ->get()->map(function ($q) {
                return [
                    'id' => $q->id,
                    'name' => $q->name,
                ];
            }) : null;

        $subjectOptions = $request->input('id') ?
            SchoolCampusCourseCurriculumSubjects::where('is_active', true)
                ->where('is_delete', false)
                ->whereHas('curriculum', function ($q) use ($request) {
                    $q->where('campus_course_id', Scholars::find(Hashids::decode($request->input('id'))[0] ?? 0)->schoolInfo->first()?->campus_course_id);
                })->get()->map(fn ($q) => [
                    'id' => $q->id,
                    'name' => $q->name,
                    'code' => $q->subject_code,
                    'unit' => $q->unit,
                ]) : null;

        $gradeOptions = $request->input('id') ? SchoolCampusGrades::where('is_active', true)
            ->where('is_delete', false)
            ->where('campus_id', Scholars::find(Hashids::decode($request->input('id'))[0] ?? 0)->schoolInfo->first()?->campus_id)
            ->get()->map(function ($q) {
                return [
                    'id' => $q->id,
                    'name' => $q->grade,
                    'is_failed' => $q->is_failed,
                    'is_incomplete' => $q->is_incomplete,
                    'is_drop' => $q->is_drop,
                    'is_active' => $q->is_active,
                ];
            }) : null;

        $schoolOptions = $request->input('id') ? SchoolCampuses::where([
            'is_delete' => false,
            'is_active' => true,
        ])
            ->when($permissions->shouldScopeToRegion($user), function ($q) use ($permissions, $user) {
                $q->whereHas('address', function ($address) use ($permissions, $user) {
                    $address->where('region_code', $permissions->regionCodeFor($user));
                });
            })
            ->get()->map(function ($campus) {
                return [
                    'id' => $campus->id,
                    'name' => $campus->generated_name,
                ];
            }) : [];

        $courseOptions = $request->input('id') ? SchoolCampusCourses::with(['course', 'campus'])->where([
            'is_delete' => false,
            'is_active' => true,
        ])
            ->whereHas(
                'campus',
                fn ($q) => $q->when($request->input('campus'), function ($q) use ($request) {
                    $q->where('generated_name', 'like', '%'.$request->input('campus').'%');
                })
            )
            ->get()
            ->map(function ($course) {
                return [
                    'id' => $course->id,
                    'name' => $course->course?->name,
                    'campus' => $course->campus?->generated_name,
                ];
            }) : [];

        $transferCourseOptions = $request->input('id') ? SchoolCampusCourses::with(['course', 'campus'])->where([
            'is_delete' => false,
            'is_active' => true,
        ])
            ->whereHas(
                'campus',
                fn ($q) => $q->when($request->input('tcampus'), function ($q) use ($request) {
                    $q->where('generated_name', 'like', '%'.$request->input('tcampus').'%');
                })
            )
            ->get()
            ->map(function ($course) {
                return [
                    'id' => $course->id,
                    'name' => $course->course?->name,
                    'campus' => $course->campus?->generated_name,
                ];
            }) : [];

        $generateSubjects = Inertia::optional(
            fn () => SchoolCampusCourseCurriculumSubjects::where('is_active', true)
                ->where('is_delete', false)
                ->whereHas('curriculum', function ($q) use ($request) {
                    $q->where('is_active', true)
                        ->where('is_delete', false)
                        ->where('campus_course_id', Scholars::find(Hashids::decode($request->input('id'))[0] ?? 0)->schoolInfo->first()?->campus_course_id);
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

        return Inertia::render(
            'Web/scholarsPage',
            [
                'scholars' => Scholars::select(
                    'scholars.id',
                    'scholars.spas_no',
                    'scholars.status_id',
                    'scholars.academic_status',
                    'scholars.program_id',
                    'scholars.category_id',
                    'scholars.type_id',
                    'scholars.award_year',
                    'scholars.activated_at',
                    'scholars.activation_token'
                )
                    ->join('scholar_profiles', 'scholar_profiles.scholar_id', '=', 'scholars.id')
                    ->with([
                        'status:id,name,icon,color_id',
                        'status.color:id,background_color,text_color',
                        'program:id,name',
                        'mainProgram:id,name',
                        'type:id,name',
                        'profile:id,scholar_id,photo,sex,fname,lname,mname,suffix,email,contact_no',
                        'schoolInfo' => fn ($q) => $q
                            ->select('id', 'scholar_id', 'campus_id', 'campus_course_id')
                            ->with([
                                'campus:id,generated_name,agency_id',
                                'campus.agency:id,name,slug',
                                'campus.address:campus_id,region_code',
                                'course' => fn ($q) => $q
                                    ->select('id', 'course_id')
                                    ->with([
                                        'course:id,name',
                                    ]),
                            ])
                            ->latest()
                            ->limit(1),

                    ])
                    ->when($request->input('search'), fn ($q) => $q->whereHas(
                        'profile',
                        fn ($q) => $q->whereRaw("CONCAT(lname, ' ', fname, ' ', COALESCE(mname, '')) ILIKE ?", ['%'.$request->input('search').'%'])
                            ->orWhere('spas_no', 'ILIKE', '%'.$request->input('search').'%')
                            ->orWhere('lname', 'ILIKE', '%'.$request->input('search').'%')
                            ->orWhere('fname', 'ILIKE', '%'.$request->input('search').'%')
                    ))
                    ->when($request->input('schools'), function ($q, $schools) {
                        $q->whereHas('schoolInfo', fn ($w) => $w->whereHas('campus', fn ($r) => $r->whereIn('generated_name', $schools)));
                    })
                    ->when($permissions->isSchoolCoordinator($user), function ($q) use ($user) {
                        $q->whereHas('schoolInfo', fn ($w) => $w->whereHas('campus', fn ($r) => $r->where('id', $user->school_id)));
                    })
                    ->when($permissions->shouldScopeToRegion($user), function ($q) use ($permissions, $user) {
                        $q->whereHas('schoolInfo.campus.address', function ($address) use ($permissions, $user) {
                            $address->where('region_code', $permissions->regionCodeFor($user));
                        });
                    })
                    ->when($request->input('programs'), function ($q, $programs) {
                        $q->whereHas('program', fn ($w) => $w->whereIn('name', $programs));
                    })
                    ->when($request->input('sub'), function ($q, $sub) {
                        $q->whereHas('type', fn ($w) => $w->whereIn('name', $sub));
                    })
                    ->when($request->input('status'), function ($q, $status) {
                        $statuses = collect($status)
                            ->map(fn ($item) => Str::upper(is_array($item) ? ($item['id'] ?? $item['name'] ?? '') : $item))
                            ->filter()
                            ->values()
                            ->all();

                        $q->whereIn(DB::raw('UPPER(academic_status)'), $statuses);
                    })
                    ->when($monitoringAcademicYear && $monitoringTermId && $monitoringSubmissionStatus !== 'all', function ($q) use ($monitoringAcademicYear, $monitoringTermId, $monitoringSubmissionStatus) {
                        if ($monitoringSubmissionStatus === 'No Submission') {
                            $q->whereDoesntHave('termRecords', function ($term) use ($monitoringAcademicYear, $monitoringTermId) {
                                $term->where('academic_year', $monitoringAcademicYear)
                                    ->where('term_id', $monitoringTermId);
                            });

                            return;
                        }

                        $q->whereHas('termRecords', function ($term) use ($monitoringAcademicYear, $monitoringTermId, $monitoringSubmissionStatus) {
                            $term->where('academic_year', $monitoringAcademicYear)
                                ->where('term_id', $monitoringTermId)
                                ->where('verification_status', $monitoringSubmissionStatus);
                        });
                    })
                    ->orderBy('scholar_profiles.lname', 'ASC')
                    ->paginate(10)
                    ->through(function ($q) use ($monitoringAcademicYear, $monitoringTermId, $academicStatusOptions) {
                        $monitoringTermRecord = null;
                        $scholarshipStatus = null;
                        $progressStatus = Str::upper($q->academic_status ?: 'NEW');
                        $progressStatusOption = $academicStatusOptions->firstWhere('name', $progressStatus);

                        if ($monitoringAcademicYear && $monitoringTermId) {
                            $monitoringTermRecord = $q->termRecords()
                                ->where('academic_year', $monitoringAcademicYear)
                                ->where('term_id', $monitoringTermId)
                                ->first();

                            $scholarshipStatus = $monitoringTermRecord
                                ? DB::connection('scholars')
                                    ->table('scholar_processes')
                                    ->where('term_record_id', $monitoringTermRecord->id)
                                    ->value('scholarship_status')
                                : null;
                        }

                        return [
                            'id' => Hashids::encode($q->id),
                            'spas_no' => $q->spas_no,
                            'photo' => $q->profile?->photo,
                            'email' => $q->profile?->email,
                            'contact_no' => $q->profile?->contact_no,
                            'sex' => $q->profile?->sex,
                            'activated_at' => $q->activated_at,
                            'activationRequested' => ! empty($q->activation_token),
                            'fullname' => trim(collect([
                                $q->profile?->lname.',',
                                $q->profile?->fname,
                                $q->profile?->mname,
                                $q->profile?->suffix,
                            ])->filter()->implode(' ')),
                            'type' => $q->type?->name,
                            'subProgram' => $q->program?->name,
                            'mainProgram' => $q->mainProgram?->name,
                            'status' => $this->academicStatusMeta($progressStatus, $progressStatusOption),
                            'submissionStatus' => $this->submissionStatusMeta($monitoringTermRecord?->verification_status),
                            'scholarshipStatus' => $scholarshipStatus,
                            'term' => $q->termRecords()->latest()->first()?->toArray(),
                            'course' => $q->schoolInfo?->first()?->course?->course?->name,
                            'school' => $q->schoolInfo?->first()?->campus?->generated_name,
                            'agency' => $q->schoolInfo?->first()?->campus?->agency?->slug,
                            'region' => $q->schoolInfo?->first()?->campus?->address?->region_array,
                        ];
                    }),
                'details' => $request->input('id') ?
                    function () use ($request, $permissions, $user) {
                        $id = Hashids::decode($request->input('id'))[0] ?? 0;
                        $q = Scholars::select(
                            'scholars.id',
                            'scholars.spas_no',
                            'scholars.status_id',
                            'scholars.academic_status',
                            'scholars.program_id',
                            'scholars.type_id',
                            'scholars.award_year'
                        )
                            ->join('scholar_profiles', 'scholar_profiles.scholar_id', '=', 'scholars.id')
                            ->with([
                                'parent',
                                'status:id,name,icon,color_id',
                                'status.color:id,background_color,text_color',
                                'address:id,scholar_id,region_code,province_code,municipality_code,barangay_code,address',
                                'program:id,name',
                                'type:id,name',
                                'profile:id,scholar_id,photo,sex,fname,lname,mname,suffix,email,contact_no,birthplace,birthdate,religion,civil_status',
                                'schoolInfo' => fn ($q) => $q
                                    ->select('id', 'scholar_id', 'campus_id', 'campus_course_id')
                                    ->with([
                                        'campus:id,generated_name,agency_id',
                                        'campus.agency:id,name,slug',
                                        'campus.address:campus_id,region_code',
                                        'course' => fn ($q) => $q
                                            ->select('id', 'course_id')
                                            ->with([
                                                'course:id,name',
                                            ]),
                                    ])
                                    ->latest()
                                    ->limit(1),
                                'termRecords' => fn ($q) => $q
                                    ->select('id', 'scholar_id', 'term_id', 'level_id', 'academic_year', 'scholar_school_id', 'term_type_id', 'verification_status')
                                    ->with([
                                        'termType:id,name',
                                        'level:id,name,others',
                                        'subjects' => fn ($q) => $q
                                            ->select('id', 'term_record_id', 'subject_id', 'grade_id')
                                            ->with([
                                                'subject:id,name,year,subject_code,unit,subject_class,semester_id',
                                                'grade:id,grade,is_failed,is_incomplete,is_drop,is_active',
                                            ]),

                                    ]),
                                'payrolls' => fn ($q) => $q->with([]),

                            ])
                            ->where('scholars.id', $id)
                            ->when($permissions->shouldScopeToRegion($user), function ($q) use ($permissions, $user) {
                                $q->whereHas('schoolInfo.campus.address', function ($address) use ($permissions, $user) {
                                    $address->where('region_code', $permissions->regionCodeFor($user));
                                });
                            })
                            ->first();

                        return [
                            'id' => Hashids::encode($q->id),
                            'spas_no' => $q?->spas_no,
                            'type' => [
                                'id' => $q?->type?->id,
                                'name' => $q?->type?->name,
                            ],
                            'program' => [
                                'id' => $q?->program?->id,
                                'name' => $q?->program?->name,
                            ],
                            'email' => $q?->profile?->email,
                            'contact_no' => $q?->profile?->contact_no,
                            'fname' => $q?->profile?->fname,
                            'mname' => $q?->profile?->mname,
                            'lname' => $q?->profile?->lname,
                            'suffix' => $q?->profile?->suffix,
                            'birthplace' => $q?->profile?->birthplace,
                            'birthdate' => Carbon::parse($q?->profile?->birthdate)->format('Y-m-d'),
                            'religion' => $q?->profile?->religion,
                            'civil_status' => $q?->profile?->civil_status,
                            'fullname' => trim(collect([
                                $q?->profile?->lname.',',
                                $q?->profile?->fname,
                                $q?->profile?->mname,
                                $q?->profile?->suffix,
                            ])->filter()->implode(' ')),

                            'academic_status' => Str::upper($q?->academic_status ?? 'NEW'),
                            'status' => [
                                'id' => Str::upper($q?->academic_status ?? 'NEW'),
                                'name' => Str::upper($q?->academic_status ?? 'NEW'),
                            ],
                            'address' => [
                                'address' => $q?->address?->address,
                                'province' => $q?->address?->province_array,
                                'region' => $q?->address?->region_array,
                                'municipality' => $q?->address?->municipality_array,
                                'barangay' => $q?->address?->barangay_array,
                            ],
                            'fullAddress' => $q?->address?->full_address,
                            'awardYear' => $q?->award_year,
                            'schoolInfoId' => $q?->schoolInfo?->first()?->id,
                            'course' => $q?->schoolInfo?->first()?->course?->course?->name,
                            'school' => $q?->schoolInfo?->first()?->campus?->generated_name,
                            'schoolInput' => [
                                'id' => $q->schoolInfo?->first()?->campus?->id,
                                'name' => $q->schoolInfo?->first()?->campus?->generated_name,
                            ],
                            'landbank' => [
                                'account_name' => $q?->landbank?->account_name,
                                'account_number' => $q?->landbank?->account_number,
                            ],
                            'courseInput' => [
                                'id' => $q?->schoolInfo?->first()?->course?->id,
                                'name' => $q?->schoolInfo?->first()?->course?->course?->name,
                                'campus' => $q?->schoolInfo?->first()?->campus?->generated_name,
                            ],
                            'region' => $q?->schoolInfo?->first()?->campus?->address?->region_array,
                            // 'tr_request' => $q?->termRecords
                            //     ->pluck('requests')
                            //     ->flatten()
                            //     ->count(),
                            'guardian' => [
                                'name' => $q?->parent?->fname,
                                'id_no' => $q?->parent?->id_no,
                                'place_issue' => $q?->parent?->id_place,
                                'date_issue' => $q?->parent?->id_date,

                            ],
                            'logs' => $q->logs
                                ->sortByDesc('created_at')
                                ->take(50)
                                ->values()
                                ->map(fn ($log) => [
                                    'created_by' => $log->created_by,
                                    'previous' => $log->previous_formatted,
                                    'changes' => $log->changes_formatted,
                                    'type' => $log->request_type,
                                    'date' => Carbon::parse($log->created_at)->format('M d, Y h:i A'),
                                ]),
                            'termGrades' => $q?->termRecords->sort(function ($left, $right) {
                                $leftSort = [
                                    $this->academicYearSortValue($left?->academic_year),
                                    (int) ($left?->level?->others ?? 0),
                                    $this->termSortValue($left?->term?->name),
                                    (int) ($left?->id ?? 0),
                                ];
                                $rightSort = [
                                    $this->academicYearSortValue($right?->academic_year),
                                    (int) ($right?->level?->others ?? 0),
                                    $this->termSortValue($right?->term?->name),
                                    (int) ($right?->id ?? 0),
                                ];

                                return $rightSort <=> $leftSort;
                            })->values()->map(function ($term) {
                                $standing = DB::connection('scholars')
                                    ->table('scholar_processes')
                                    ->where('term_record_id', $term->id)
                                    ->value('scholarship_status');

                                $subjects = $term->subjects->map(function ($sub) {
                                    $grade = $sub->grade;
                                    $gradeValue = is_numeric($grade?->grade) ? (float) $grade->grade : null;
                                    $unit = is_numeric($sub->subject?->unit) ? (float) $sub->subject->unit : 0;
                                    $isAcademic = Str::lower($sub->subject?->subject_class ?? '') === 'academic';
                                    $isCounted = $isAcademic
                                        && $gradeValue !== null
                                        && ! ($grade?->is_drop || $grade?->is_incomplete);

                                    return [
                                        'subject' => [
                                            'id' => $sub->id,
                                            'name' => $sub->subject?->name,
                                            'code' => $sub->subject?->subject_code,
                                            'unit' => $sub->subject?->unit,
                                        ],
                                        'grade' => [
                                            'id' => $sub->grade?->id,
                                            'grade' => $sub->grade?->grade,
                                            'is_failed' => $sub->grade?->is_failed,
                                            'is_incomplete' => $sub->grade?->is_incomplete,
                                            'is_drop' => $sub->grade?->is_drop,
                                            'is_active' => $sub->grade?->is_active,
                                        ],
                                        'request' => [
                                            'id' => null,
                                            'grade' => null,
                                            'is_failed' => null,
                                            'is_incomplete' => null,
                                            'is_drop' => null,
                                            'is_active' => null,
                                        ],
                                        'total' => $isCounted ? round($gradeValue * $unit, 2) : null,
                                        'is_academic' => $isAcademic,
                                        'is_counted' => $isCounted,
                                    ];
                                });
                                $countedSubjects = $subjects->where('is_counted', true);
                                $totalUnits = $countedSubjects->sum(fn ($subject) => (float) ($subject['subject']['unit'] ?? 0));
                                $totalGradePoints = $countedSubjects->sum(fn ($subject) => (float) ($subject['total'] ?? 0));

                                return [
                                    'id' => $term->id,
                                    'termType' => $term->term->name,
                                    'files' => StudentDocument::where('term', $term->id)->get(),
                                    'academic_year' => $term->academic_year,
                                    'gradeRequest' => false,
                                    'subjects' => $subjects,
                                    'summary' => [
                                        'units' => $totalUnits,
                                        'total' => round($totalGradePoints, 2),
                                        'average' => $totalUnits > 0 ? number_format($totalGradePoints / $totalUnits, 2, '.', '') : null,
                                    ],
                                    'scholarshipStatus' => $standing,
                                ];
                            }),
                            'financialAid' => [
                                'grandTotal' => number_format(
                                    $q?->payrolls->sum('grand_total'), 2,
                                ),
                                'approvedTotal' => number_format(
                                    $q?->payrolls()->where('status', 'approved')->get()->values()->sum('grand_total'), 2,
                                ),
                                'totalWithheld' => number_format(
                                    $q?->payrolls->sum('total_withheld'), 2,
                                ),
                                'clothing' => number_format(
                                    $q->payrolls()
                                        ->with('allowances.allowanceType')
                                        ->get()
                                        ->flatMap->allowances
                                        ->filter(function ($allowance) {
                                            return $allowance->allowanceType?->code === 'clothing';
                                        })
                                        ->sum('amount'),
                                    2
                                ),
                                'connectivity' => number_format(
                                    $q->payrolls()
                                        ->with('allowances.allowanceType')
                                        ->get()
                                        ->flatMap->allowances
                                        ->filter(function ($allowance) {
                                            return $allowance->allowanceType?->code === 'connectivity';
                                        })
                                        ->sum('amount'),
                                    2
                                ),
                                'totalAllowances' => number_format(
                                    $q->payrolls()
                                        ->with('allowances.allowanceType')
                                        ->get()
                                        ->flatMap->allowances

                                        ->sum('amount'),
                                    2
                                ),
                                'monthly' => $q?->payrolls()
                                    ->orderBy('created_at', 'desc')
                                    ->get()
                                    ->map(function ($payroll) {
                                        return [
                                            'period' => $payroll->period,
                                            'status' => $payroll->status,
                                            'grandTotal' => number_format($payroll->grand_total, 2),
                                            'logs' => $payroll->logs->map(function ($log) {
                                                return [

                                                    'action' => $log->status,
                                                    'remarks' => $log->remarks,
                                                    'created_at' => Carbon::parse($log->created_at)->format('F d, Y h:i A'),
                                                    'created_by' => $log->action_by,
                                                ];
                                            }),
                                            'stipends' => $payroll->stipends->map(function ($stipend) {
                                                return [
                                                    'month' => $stipend->month,
                                                    'amount' => number_format($stipend->amount, 2),
                                                ];
                                            }),
                                            'financial' => $payroll->allowances->map(function ($allowance) {
                                                return [
                                                    'code' => $allowance->allowanceType?->code,
                                                    'name' => $allowance->allowanceType?->name,
                                                    'description' => $allowance->allowanceType?->description,
                                                    'amount' => number_format($allowance->amount, 2),
                                                ];
                                            }),
                                            'totalStipends' => number_format($payroll->stipends->sum('amount'), 2),
                                        ];
                                    }),

                            ],

                        ];
                    } : null,
                'resultSearch' => request('findAddress')
                    ? ($location->getFullAddress(request('findAddress'), false) ?? [])
                    : [],
                'schoolFilter' => $schoolFilter,
                'programFilter' => $programFilter,
                'scholarTypeFilter' => $scholarTypeFilter,
                'statusFilter' => $statusFilter,
                'monitoringYearOptions' => $monitoringYearOptions,
                'monitoringTermOptions' => $monitoringTermOptions,
                'submissionStatusOptions' => $submissionStatusOptions,
                'standingOptions' => $standingOptions,
                'selectedMonitoringYear' => $selectedMonitoringYear,
                'selectedMonitoringTerm' => $selectedMonitoringTerm,
                'selectedSubmissionStatus' => $monitoringSubmissionStatus
                    ? $submissionStatusOptions->firstWhere('id', $monitoringSubmissionStatus)
                    : null,
                'statusOptions' => $statusOptions,
                'programOptions' => $programOptions,
                'subProgramOptions' => $subProgramOptions,
                'yearOptions' => $yearOptions,
                'transferCourseOptions' => $transferCourseOptions,
                'termOptions' => $termOptions,
                'subjectOptions' => $subjectOptions,
                'gradeOptions' => $gradeOptions,
                'schoolOptions' => $schoolOptions,
                'courseOptions' => $courseOptions,
                'generateSubjects' => $generateSubjects,
                'OpenDetail' => $request->input('id') ?? null,
                'filterSearch' => $request->input('search') ?? null,
                'filterSchool' => $request->input('schools') != null ? Scholars::with([
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
                    ->values()
                    : null,
            ]
        );
    }

    public function update(string $id, string $type, Request $request)
    {
        try {
            if (! app(SystemPermissions::class)->can(Auth::user(), 'scholars.update')) {
                abort(403, 'Unauthorized');
            }

            $decodedId = Hashids::decode($id)[0] ?? 0;
            $scholar = Scholars::findOrFail($decodedId);

            if ($type == 'personal') {
                $data = $request->validate([
                    'first_name' => 'nullable|string|max:255',
                    'middle_name' => 'nullable|string|max:255',
                    'last_name' => 'nullable|string|max:255',
                    'suffix' => 'nullable|string|max:255',
                    'email' => ['nullable', 'email', 'max:255', Rule::unique('scholar_profiles', 'email')->ignore($scholar->profile?->id)],
                    'contact_no' => 'nullable|string|max:20',
                    'birth_place' => 'nullable|string|max:255',
                    'birth_date' => 'nullable|date',
                    'religion' => 'nullable|string|max:255',
                    'civil_status' => 'nullable|string|max:255',
                    'fulladdress' => 'nullable',
                    'address' => 'nullable',
                    // // Scholarship
                    'program' => 'nullable',
                    'sub_program' => 'nullable',
                    'award_year' => 'nullable',
                    'status' => 'nullable',
                    'school' => 'nullable',
                    'course' => 'nullable',
                    'schoolId' => 'nullable',

                    // // Guardian
                    'guardian_name' => 'nullable|string|max:255',
                    'guardian_id_no' => 'nullable|string|max:255',
                    'guardian_place_issue' => 'nullable|string|max:255',
                    'guardian_date_issue' => 'nullable|date',
                    // // Landbank
                    'acc_name' => 'nullable|string|max:255',
                    'acc_no' => 'nullable|string|max:16',
                ]);

                $slice = explode('-', $data['fulladdress']['id']);

                $scholar->update([
                    'program_id' => $data['program']['id'],
                    'type_id' => $data['sub_program']['id'],
                    'award_year' => Carbon::parse($data['award_year'])->format('Y') + 1,
                    'academic_status' => Str::upper($data['status']['name'] ?? $data['status']['id'] ?? 'NEW'),
                ]);

                $profile = $scholar->profile()->updateOrCreate(
                    ['scholar_id' => $scholar->id],
                    [
                        'fname' => Str::upper($data['first_name']),
                        'mname' => Str::upper($data['middle_name']) ?? null,
                        'lname' => Str::upper($data['last_name']),
                        'suffix' => Str::upper($data['suffix']) ?? null,
                        'email' => $data['email'],

                        'contact_no' => $data['contact_no'] ?? null,
                        'birthplace' => $data['birth_place'] ?? null,
                        'birthdate' => Carbon::parse($data['birth_date'])->setTimezone('Asia/Manila')
                            ->format('Y-m-d'),
                        'religion' => Str::upper($data['religion']) ?? null,
                        'civil_status' => Str::upper($data['civil_status']) ?? null,
                    ]
                );
                if ($profile->wasChanged()) {
                    $changes = $profile->getChanges();
                    $prev = $profile->getPrevious();

                    ActivityLogs::create([
                        'previous_data' => $prev,
                        'changes_data' => $changes,
                        'request_type' => 'profile',
                        'created_by' => Auth::user()->profile->fullname,
                        'scholar_id' => $decodedId,
                    ]);

                }
                $address = $scholar->address()->updateOrCreate(
                    ['scholar_id' => $scholar->id],
                    [
                        'address' => $data['address'] ?? null,
                        'barangay_code' => $slice[0] ?? null,
                        'municipality_code' => $slice[1] ?? null,
                        'province_code' => $slice[2] ?? null,
                        'region_code' => $slice[3] ?? null,
                    ]
                );

                if ($address->wasChanged()) {
                    $changes = $address->getChanges();
                    $prev = $address->getPrevious();

                    ActivityLogs::create([
                        'previous_data' => $prev,
                        'changes_data' => $changes,
                        'request_type' => 'address',
                        'created_by' => Auth::user()->profile->fullname,
                        'scholar_id' => $decodedId,
                    ]);

                }
                $school = $scholar->schoolInfo()->updateOrCreate(

                    [
                        'id' => $data['schoolId'],
                        'scholar_id' => $scholar->id,
                    ],
                    [
                        'campus_id' => $data['school']['id'],
                        'campus_course_id' => $data['course']['id'],
                    ]
                );

                if ($school->wasChanged()) {
                    $changes = Arr::except($school->getChanges(), [
                        'updated_at',
                    ]);
                    $previous = Arr::only($school->getOriginal(), array_keys($changes));

                    ActivityLogs::create([
                        'previous_data' => $previous,
                        'changes_data' => $changes,
                        'request_type' => 'school',
                        'created_by' => Auth::user()->profile->fullname,
                        'scholar_id' => $decodedId,
                    ]);

                }

                $landbank = $scholar->landbank()->updateOrCreate(
                    ['scholar_id' => $scholar->id],
                    [
                        'account_name' => $data['acc_name'] ?? null,
                        'account_number' => $data['acc_no'] ?? null,
                    ]
                );

                if ($landbank->wasChanged()) {

                    $changes = Arr::except($landbank->getChanges(), [
                        'updated_at',
                    ]);

                    $previous = Arr::except($landbank->getPrevious(), ['updated_at']);

                    ActivityLogs::create([
                        'previous_data' => $previous,
                        'changes_data' => $changes,
                        'request_type' => 'landbank',
                        'created_by' => Auth::user()->profile->fullname,
                        'scholar_id' => $decodedId,
                    ]);

                }

                $scholar->parent()->updateOrCreate(
                    ['scholar_id' => $scholar->id],
                    [
                        'fname' => $data['guardian_name'] ?? null,
                        'id_no' => $data['guardian_id_no'] ?? null,
                        'id_place' => $data['guardian_place_issue'] ?? null,
                        'id_date' => $data['guardian_date_issue'] ?? null,
                    ]
                );

                return redirect()->back()->with([
                    'flash' => [
                        'status' => 'success',
                        'title' => 'Scholar Updated',
                        'message' => 'Scholar information successfully updated.',
                    ],
                ]);
            }
            // if ($type == 'grades') {

            //     $data = $request->validate([
            //         'school' => 'nullable',
            //         'course' => 'nullable',
            //         'term' => 'required',
            //         'year' => 'required',
            //         'academic_year' => 'required',
            //         'subjects' => 'required',
            //         'subjects.*.grade' => 'required',
            //         'subjects.*.subject' => 'required',
            //     ]);

            //     $termRecord = $scholar->termRecords()->updateOrCreate(
            //         [
            //             'term_id' => $data['term']['id'],
            //             'level_id' => $data['year']['id'],
            //             'academic_year' => $data['academic_year'],
            //         ],
            //         [
            //             'scholar_school_id' => $scholar->schoolInfo->first()?->id,
            //             'term_type_id' => $scholar->schoolInfo->first()->campus->term?->id ?? null,
            //             'level_id' => $data['year']['id'] ?? null,
            //             'term_id' => $data['term']['id'] ?? null,
            //             'academic_year' => $data['academic_year'] ?? null,
            //         ]
            //     );
            //     foreach ($data['subjects'] as $key => $value) {
            //         $termRecord->subjects()->updateOrCreate(
            //             [
            //                 'subject_id' => $value['subject']['id'],
            //                 'grade_id' => $value['grade']['id'],
            //             ],
            //             [
            //                 'grade_id' => $value['grade']['id'],
            //                 'remarks' => 'created by system',
            //             ]
            //         );
            //     }

            //     return redirect()->back()->with([
            //         'flash' => [
            //             'status' => 'success',
            //             'title' => 'Grade Saved',
            //             'message' => 'Grade record saved successfully.',
            //         ],
            //     ]);
            // }
        } catch (\Throwable $th) {

            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Save Failed',
                'message' => $th->getMessage(),
            ]);
        }
    }

    public function activation(string $id)
    {
        $decodedId = Hashids::decode($id)[0] ?? 0;
        $activation = Str::random(64);
        $user = Scholars::with(['profile'])->findOrFail($decodedId);

        if (! $user->profile?->email) {
            throw new Exception('User has no email address.');
        }

        $activation = Str::random(60);

        $user->update([
            'activation_token' => $activation,
        ]);

        $url = 'http://172.16.8.98:85/activation?token='.$activation;
        Mail::to($user->profile->email)
            ->send(new activationLinkMail($url));

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title' => 'Activation Link!',
            'message' => 'The link has been successfully send.',
        ]);
    }

    public function profileRequest(string $type, Request $request)
    {
        $permission = $type === 'accept' ? 'profile-requests.approve' : 'profile-requests.reject';
        if (! app(SystemPermissions::class)->can(Auth::user(), $permission)) {
            abort(403, 'Unauthorized');
        }

        $data = $request->input('data');
        $scholar = Scholars::where('spas_no', $data['spas_no'])->firstOrFail();

        if ($type == 'accept') {

            $profile = $scholar->profile;
            $address = $scholar->address;

            $previous = array_merge(
                $profile ? $profile->only([
                    'email',
                    'contact_no',
                    'civil_status',
                ]) : [],
                $address ? $address->only([
                    'address',
                    'barangay_code',
                    'municipality_code',
                    'province_code',
                    'region_code',
                ]) : []
            );

            $input = [
                'email' => $data['email'],
                'contact_no' => $data['contact_no'],
                'civil_status' => $data['civil_status'],
                'address' => $data['address'],
                'barangay_code' => LocationBarangays::firstWhere('name', $data['barangay'])?->code,
                'municipality_code' => LocationCity::firstWhere('name', $data['municipality'])?->code,
                'province_code' => LocationProvinces::firstWhere('name', $data['province'])?->code,
                'region_code' => LocationRegions::firstWhere('region', $data['region'])?->code,
            ];
            $filteredInput = collect($input)
                ->except(['created_by', 'created_at'])
                ->toArray();

            $changes = array_diff_assoc($filteredInput, $previous);

            $scholar->profile()->update([
                'email' => $data['email'],
                'contact_no' => $data['contact_no'],
                'civil_status' => $data['civil_status'],
            ]);

            $scholar->address()->update([
                'address' => $data['address'],
                'barangay_code' => LocationBarangays::firstWhere('name', $data['barangay'])?->code,
                'municipality_code' => LocationCity::firstWhere('name', $data['municipality'])?->code,
                'province_code' => LocationProvinces::firstWhere('name', $data['province'])?->code,
                'region_code' => LocationRegions::firstWhere('region', $data['region'])?->code,
            ]);

            $scholar->requestHistory()->create([
                'request_type' => 'profile',
                'previous' => array_intersect_key($previous, $changes),
                'changes' => $changes,
                'created_by' => Auth::user()->profile->fullname,
                'created_at' => now(),
                'request_no' => $data['count'],
            ]);

            $scholar->profileRequest()->update([
                'status' => 'approved',
                'reviewed_at' => now(),
                'reviewed_by' => Auth::user()->profile->fullname,
            ]);
        } else {

            $validation = Validator::make($data, [
                'remarks' => 'required|string|max:255',
            ]);
            if ($validation->fails()) {
                return redirect()->back()->with('flash', [
                    'status' => 'error',
                    'title' => 'Validation Failed',
                    'message' => 'Please fill in the remarks field.',
                ]);
            }
            $scholar->profileRequest()->update([
                'status' => 'rejected',
                'reviewed_at' => Carbon::now(),
                'reviewed_by' => Auth::user()->profile->fullname,
                'remarks' => $data['remarks'],
            ]);
        }

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title' => $type === 'accept'
                ? 'Scholar info request approved'
                : 'Scholar info request rejected',
            'message' => $type === 'accept'
                ? 'The scholar information change request has been approved.'
                : 'The scholar information change request has been rejected.',
        ]);
    }

    public function landbankRequest(string $type, Request $request)
    {
        $permission = $type === 'accept' ? 'landbank-requests.approve' : 'landbank-requests.reject';
        if (! app(SystemPermissions::class)->can(Auth::user(), $permission)) {
            abort(403, 'Unauthorized');
        }

        $data = $request->input('data');
        $scholar = Scholars::where('spas_no', $data['spas_no'])->firstOrFail();

        if ($type == 'accept') {

            $landbank = $scholar->landbank()->first();

            // Capture previous values BEFORE update
            $previous = $landbank ? $landbank->only([
                'account_number',
                'account_name',
                'uploaded_type',
                'uploaded_file',
            ]) : [];

            // New input
            $input = [
                'account_number' => $data['no'],
                'account_name' => $data['name'],
                'uploaded_type' => $data['type'],
                'uploaded_file' => $data['file'],
                'created_by' => Auth::user()->profile->fullname,
                'updated_by' => Auth::user()->profile->fullname,
            ];

            $filteredInput = collect($input)
                ->except(['created_by', 'updated_by'])
                ->toArray();
            $changes = $landbank
                ? array_diff_assoc($filteredInput, $previous)
                : $input;

            // Save / update record
            $landbank = $scholar->landbank()->updateOrCreate(
                [],
                $input
            );

            // Store history
            $scholar->requestHistory()->create([
                'request_type' => 'landbank',
                'previous' => $previous,
                'changes' => $changes,
                'created_by' => Auth::user()->profile->fullname,
                'created_at' => Carbon::now(),
                'request_no' => $data['count'],
            ]);

            // Update request status
            $scholar->landbankRequest()->where('id', $data['request_id'])->update([
                'status' => 'approved',
                'reviewed_at' => Carbon::now(),
                'reviewed_by' => Auth::user()->profile->fullname,
            ]);
        } else {

            $validation = Validator::make($data, [
                'reject' => 'required|string|max:255',
            ]);

            if ($validation->fails()) {
                return redirect()->back()->with('flash', [
                    'status' => 'error',
                    'title' => 'Validation Failed',
                    'message' => 'Please fill in the remarks field.',
                ]);
            }
            $scholar->landbankRequest()->where('id', $data['request_id'])->update([
                'status' => 'rejected',
                'reviewed_at' => Carbon::now(),
                'reviewed_by' => Auth::user()->profile->fullname,
                'rejection_reason' => $data['reject'],
            ]);
        }

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title' => $type === 'accept'
                ? 'Landbank request approved'
                : 'Landbank request rejected',
            'message' => $type === 'accept'
                ? 'The Landbank change request has been approved.'
                : 'The Landbank change request has been rejected.',
        ]);
    }

    public function gradeRequest(string $type, Request $request)
    {
        $permission = $type === 'accept' ? 'grade-submissions.approve' : 'grade-submissions.reject';
        if (! app(SystemPermissions::class)->can(Auth::user(), $permission)) {
            abort(403, 'Unauthorized');
        }

        $data = $request->input('data');

        if ($type == 'accept') {

            $validation = Validator::make($data[0], [
                'scholarshipStatus' => 'required|array|max:255',
            ]);

            if ($validation->fails()) {
                return redirect()->back()->with('flash', [
                    'status' => 'error',
                    'title' => 'Validation Failed',
                    'message' => 'Please fill in the scholarship status.',
                ]);
            }

            $scholarshipStatus = $data[0]['scholarshipStatus']['name']
                ?? $data[0]['scholarshipStatus']['id']
                ?? null;
            $scholarshipStatus = Str::upper($scholarshipStatus);

            $terms = ScholarTerm::with('scholar:id,spas_no')
                ->whereIn('id', collect($data)->pluck('id'))
                ->get();

            foreach ($terms as $term) {
                DB::table('scholar_term_records')
                    ->where('id', $term->id)
                    ->update([
                        'verification_status' => 'approved',
                        'verified_by' => Auth::id(),
                        'updated_at' => now(),
                    ]);

                $term->forceFill([
                    'verification_status' => 'approved',
                    'verified_by' => Auth::id(),
                ]);

                DB::connection('scholars')
                    ->table('scholar_processes')
                    ->updateOrInsert(
                        ['term_record_id' => $term->id],
                        [
                            'spas_no' => $term->scholar?->spas_no,
                            'scholarship_status' => $scholarshipStatus,
                            'submission' => 'APPROVED',
                            'payroll' => 'NOT SUBMITTED',
                            'updated_at' => now(),
                            'updated_by' => Auth::user()->profile->fullname,
                        ]
                    );

                app(StipendController::class)->autoAttachApprovedTerm($term->fresh());
            }
        } else {

            $validation = Validator::make($data[0], [
                'remarks' => 'required|string|max:255',
            ]);

            if ($validation->fails()) {
                return redirect()->back()->with('flash', [
                    'status' => 'error',
                    'title' => 'Validation Failed',
                    'message' => 'Please fill in the remarks field.',
                ]);
            }

            $remarks = collect($data)->firstWhere('status', 'submitted')['remarks'];

            $terms = ScholarTerm::with('scholar:id,spas_no')
                ->whereIn('id', collect($data)->pluck('id'))
                ->get();

            foreach ($terms as $term) {
                DB::table('scholar_term_records')
                    ->where('id', $term->id)
                    ->update([
                        'verification_status' => 'rejected',
                        'rejection_reason' => $remarks,
                        'verified_by' => Auth::id(),
                        'updated_at' => now(),
                    ]);

                $term->forceFill([
                    'verification_status' => 'rejected',
                    'rejection_reason' => $remarks,
                    'verified_by' => Auth::id(),
                ]);

                DB::connection('scholars')
                    ->table('scholar_processes')
                    ->updateOrInsert(
                        ['term_record_id' => $term->id],
                        [
                            'spas_no' => $term->scholar?->spas_no,
                            'submission' => 'REJECTED',
                            'payroll' => 'NOT SUBMITTED',
                            'updated_at' => now(),
                            'updated_by' => Auth::user()->profile->fullname,
                        ]
                    );
            }
        }

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title' => $type === 'accept'
                ? 'Grade request approved'
                : 'Grade request rejected',
            'message' => $type === 'accept'
                ? 'The grade request has been approved.'
                : 'The grade request has been rejected.',
        ]);
    }

    public function transfer(string $id, string $type, Request $request)
    {
        $decodedId = Hashids::decode($id)[0] ?? 0;
        $scholar = Scholars::findOrFail($decodedId);
        if ($type == 'school') {
            $data = $request->validate([
                'school' => 'required',
                'course' => 'required',
            ]);
            $scholar->schoolInfo()->create(
                [
                    'campus_id' => $data['school']['id'],
                    'campus_course_id' => $data['course']['id'],
                ]
            );

            return redirect()->back()->with([
                'flash' => [
                    'status' => 'success',
                    'title' => 'Course Transferred',
                    'message' => 'Course transfer successful.',
                ],
            ]);
        } else {
            return redirect()->back()->with([
                'flash' => [
                    'status' => 'error',
                    'title' => 'Transfer Failed',
                    'message' => 'Invalid transfer type.',
                ],
            ]);
        }
    }
}
