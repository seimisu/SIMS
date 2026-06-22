<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Mail\activationLinkMail;
use App\Models\ListPrograms;
use App\Models\ListReferences;
use App\Models\ListStatuses;
use App\Models\LocationBarangays;
use App\Models\LocationCity;
use App\Models\LocationProvinces;
use App\Models\LocationRegions;
use App\Models\requestHistory;
use App\Models\Scholars;
use App\Models\ScholarSchoolGrades;
use App\Models\SchoolCampusCourseCurriculumSubjects;
use App\Models\SchoolCampusCourses;
use App\Models\SchoolCampuses;
use App\Models\SchoolCampusGrades;
use App\Models\StudentGrade;
use App\Models\StudentGradeRequest;
use App\Models\studentLandbankRequest;
use App\Models\StudentProfileRequest;
use App\Models\StudentSubject;
use App\Models\StudentSubjectRequest;
use App\References\LocationClass;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Vinkla\Hashids\Facades\Hashids;

class Scholar1Controller extends Controller
{
    public function index(Request $request, LocationClass $location)
    {
        $schoolFilter = Inertia::optional(
            fn () => Scholars::with([
                'schoolInfo' => fn ($q) => $q
                    ->select('id', 'scholar_id', 'campus_id')
                    ->with('campus:id,generated_name')
                    ->latest()
                    ->limit(1),
            ])
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

        $statusFilter = Inertia::optional(
            fn () => Scholars::with([
                'status:id,name',
            ])
                ->get()
                ->map(function ($q) {
                    return [
                        'id' => $q->status->id,
                        'name' => $q->status->name,
                    ];
                })
                ->filter()
                ->unique('id')
                ->values()
        );

        $statusOptions = $request->input('id') ? ListStatuses::where('type', 'progress')
            ->where('is_active', true)
            ->where('is_delete', false)
            ->get()->map(function ($q) {
                return [
                    'id' => $q->id,
                    'name' => $q->name,
                    'icon' => $q->icon,
                    'bcolor' => $q->color?->background_color,
                    'tcolor' => $q->color?->text_color,
                ];
            }) : null;

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
        ])->get()->map(function ($campus) {
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

        $termSubjectRecordIds = StudentSubject::whereHas('subjectRequests', function ($q) {
            $q->where('status', 'pending');
        })->pluck('term_record_id')->toArray();
        $termGradeRecordIds = StudentGrade::whereHas('gradeRequests', function ($q) {
            $q->where('status', 'submitted');
        })->pluck('term_record_id')->toArray();

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
                'request_cnt' => collect([
                    'landbank' => studentLandbankRequest::where('status', 'pending')->count(),
                    'profile' => StudentProfileRequest::where('status', 'pending')->count(),
                    'grades' => '',
                ]),
                'grade_request_cnt' => Str::of(
                    StudentGrade::whereHas('gradeRequests', function ($q) {
                        $q->where('status', 'submitted');
                    })->count()
                )->toString(),
                'scholars' => Scholars::select(
                    'scholars.id',
                    'scholars.spas_no',
                    'scholars.status_id',
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
                        'termRecords' => fn ($q) => $q
                            ->select('id', 'scholar_id', 'term_id', 'level_id', 'academic_year', 'scholar_school_id', 'term_type_id')
                            ->with([
                                'requests' => fn ($q) => $q
                                    ->select('id', 'spas_no', 'term_record_id', 'status', 'requested_at', 'updated_at', 'updated_by', 'remarks')
                                    ->with([
                                        'subjectRequests.subject:id,name,year,subject_code,unit,subject_class,semester_id',
                                    ]),
                                'gradeRequests' => fn ($q) => $q
                                    ->where('status', 'submitted'),
                            ]),
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
                    ->when(Auth::user()->role_array['name'] == 'School Coordinator', function ($q) {
                        $q->whereHas('schoolInfo', fn ($w) => $w->whereHas('campus', fn ($r) => $r->where('id', Auth::user()->school_id)));
                    })
                    ->when($request->input('programs'), function ($q, $programs) {
                        $q->whereHas('program', fn ($w) => $w->whereIn('name', $programs));
                    })
                    ->when($request->input('sub'), function ($q, $sub) {
                        $q->whereHas('type', fn ($w) => $w->whereIn('name', $sub));
                    })
                    ->when($request->input('status'), function ($q, $status) {
                        $q->whereHas('status', fn ($w) => $w->whereIn('name', $status));
                    })

                    ->when($request->input('subjectRequest'), function ($q) use ($termSubjectRecordIds) {
                        $q->whereHas('termRecords', function ($w) use ($termSubjectRecordIds) {
                            $w->whereIn('id', $termSubjectRecordIds);
                        });
                    })
                    ->when($request->input('gradeRequest'), function ($q) use ($termGradeRecordIds) {
                        $q->whereHas('termRecords', function ($w) use ($termGradeRecordIds) {
                            $w->whereIn('id', $termGradeRecordIds);
                        });
                    })
                    ->orderBy('scholar_profiles.lname', 'ASC')
                    ->paginate(10)
                    ->through(fn ($q) => [
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
                        'status' => [
                            'name' => Str::ucfirst($q->status?->name),
                            'bcolor' => $q->status?->color?->background_color,
                            'tcolor' => $q->status?->color?->text_color,
                            'icon' => $q->status?->icon,
                        ],
                        'course' => $q->schoolInfo?->first()?->course?->course?->name,
                        'school' => $q->schoolInfo?->first()?->campus?->generated_name,
                        'awardyear' => $q->award_year,
                        'agency' => $q->schoolInfo?->first()?->campus?->agency?->slug,
                        'region' => $q->schoolInfo?->first()?->campus?->address?->region_array,
                        'request' => $q->termRecords
                            ->pluck('requests')
                            ->flatten()
                            ->isNotEmpty(),
                        'gradeRequest' => $q->termRecords
                            ->pluck('gradeRequests')
                            ->flatten()
                            ->isNotEmpty(),
                        'personalRequest' => [
                            'count' => $q->profileRequest->where('status', 'pending')->count(),
                            'hasRequest' => $q->profileRequest->where('status', 'pending')->isNotEmpty(),
                        ],

                    ]),
                'personalRequest' => Inertia::optional(
                    fn () => Scholars::where('id', Hashids::decode($request->input('id'))[0] ?? 0)
                        ->with([
                            'profileRequest',
                            'profile',
                            'address',
                        ])
                        ->get()
                        ->flatMap(function ($scholar) {
                            return $scholar->profileRequest->map(function ($q, $index) use ($scholar) {

                                return [
                                    'count' => Carbon::parse($q->requested_at)->format('Ymd')
                                        .'-'.str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                                    'purpose' => $q->purpose,
                                    'address' => $q->address,
                                    'barangay' => $q->barangay,
                                    'municipality' => $q->municipality,
                                    'province' => $q->province,
                                    'region' => $q->region,
                                    'civil_status' => $q->civil_status,
                                    'contact_no' => $q->contact_no,
                                    'email' => $q->email,

                                    'fullAddress' => collect([
                                        $q->address,
                                        $q->barangay,
                                        $q->municipality,
                                        $q->province,
                                        $q->region,
                                    ])->filter()->implode(', '),
                                    'fullAddressStored' => $scholar?->address?->full_address,
                                    'file_type' => $q->proof_type,
                                    'remarks' => $q->remarks,
                                    'requested_at' => Carbon::parse($q->requested_at)->diffForHumans(),
                                    'request_date' => Carbon::parse($q->requested_at)->format('F d, Y h:i A'),
                                    'reviewed_at' => $q->reviewed_at
                                        ? Carbon::parse($q->reviewed_at)->diffForHumans()
                                        : null,
                                    'reviewed_by' => $q->reviewed_by,
                                    'status' => $q->status,
                                    'file' => $q->proof,
                                    'emailStored' => $scholar->profile->email,
                                    'contactStored' => $scholar->profile->contact_no,
                                    'civilStored' => $scholar->profile->civil_status,
                                    'spas_no' => $scholar->spas_no,
                                    'records' => requestHistory::where('request_no', Carbon::parse($q->requested_at)->format('Ymd')
                                       .'-'.str_pad($index + 1, 3, '0', STR_PAD_LEFT))->where('request_type', 'profile')->first(),
                                ];
                            });
                        })
                        ->values()
                ),
                'landbankRequest' => Inertia::optional(
                    fn () => Scholars::where('id', Hashids::decode($request->input('id'))[0] ?? 0)
                        ->with([
                            'landbankRequest',
                            'landbank',
                        ])
                        ->get()
                        ->flatMap(function ($scholar) {
                            return $scholar->landbankRequest->map(function ($q, $index) use ($scholar) {

                                return [
                                    'count' => Carbon::parse($q->requested_at)->format('Ymd')
                                        .'-'.str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                                    'spas_no' => $q->spas_no,
                                    'requested_at' => Carbon::parse($q->requested_at)->diffForHumans(),
                                    'reviewed_at' => $q->reviewed_at
                                        ? Carbon::parse($q->reviewed_at)->diffForHumans()
                                        : null,
                                    'request_date' => Carbon::parse($q->requested_at)->format('F d, Y h:i A'),
                                    'reviewed_by' => $q->reviewed_by,
                                    'nameStored' => $scholar->landbank?->account_name,
                                    'noStored' => $scholar->landbank?->account_number,
                                    'status' => $q->status,
                                    'name' => $q->acc_name,
                                    'reject' => $q->rejection_reason,
                                    'no' => $q->acc_no,
                                    'file' => $q->uploaded_file,
                                    'remarks' => $q->request_purpose,
                                    'type' => $q->uploaded_type,
                                    'records' => requestHistory::where('request_no', Carbon::parse($q->requested_at)->format('Ymd')
                                        .'-'.str_pad($index + 1, 3, '0', STR_PAD_LEFT))->where('request_type', 'landbank')->first(),
                                ];
                            });
                        })
                        ->values()),
                'details' => $request->input('id') ?
                    function () use ($request) {
                        $id = Hashids::decode($request->input('id'))[0] ?? 0;
                        $q = Scholars::select(
                            'scholars.id',
                            'scholars.spas_no',
                            'scholars.status_id',
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
                                    ->select('id', 'scholar_id', 'term_id', 'level_id', 'academic_year', 'scholar_school_id', 'term_type_id')
                                    ->with([
                                        'requests' => fn ($q) => $q
                                            ->select('id', 'spas_no', 'term_record_id', 'status', 'requested_at', 'updated_at', 'updated_by', 'remarks')
                                            ->with([
                                                'subjectRequests.subject:id,name,year,subject_code,unit,subject_class,semester_id',
                                            ]),
                                        'termType:id,name',
                                        'level:id,name,others',
                                        'subjects' => fn ($q) => $q
                                            ->select('id', 'term_record_id', 'subject_id', 'grade_id')
                                            ->with([
                                                'subject:id,name,year,subject_code,unit,subject_class,semester_id',
                                                'grade:id,grade,is_failed,is_incomplete,is_drop,is_active',
                                            ]),
                                        'gradeRequests' => fn ($q) => $q
                                            ->where('status', 'submitted'),

                                    ]),
                            ])
                            ->where('scholars.id', $id)
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

                            'status' => [
                                'id' => $q?->status?->id,
                                'name' => $q?->status?->name,
                                'bcolor' => $q?->status?->color?->background_color,
                                'tcolor' => $q?->status?->color?->text_color,
                                'icon' => $q?->status?->icon,
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
                            'courseInput' => [
                                'id' => $q?->schoolInfo?->first()?->course?->id,
                                'name' => $q?->schoolInfo?->first()?->course?->course?->name,
                                'campus' => $q?->schoolInfo?->first()?->campus?->generated_name,
                            ],
                            'region' => $q?->schoolInfo?->first()?->campus?->address?->region_array,
                            'tr_request' => $q?->termRecords
                                ->pluck('requests')
                                ->flatten()
                                ->count(),
                            'grade_request' => $q?->termRecords->pluck('gradeRequests')->flatten()->count(),

                            'guardian' => [
                                'name' => $q?->parent?->fname,
                                'id_no' => $q?->parent?->id_no,
                                'place_issue' => $q?->parent?->id_place,
                                'date_issue' => $q?->parent?->id_date,

                            ],
                            'termGrades' => $q?->termRecords->sortBy([
                                fn ($term) => $term?->level?->others,
                                fn ($term) => $term?->term?->name,

                            ])->values()->map(function ($term) {
                                return [
                                    'id' => $term->id,
                                    'term' => $term->id
                                        ? $term->only('id', 'name')
                                        : null,
                                    'level' => $term?->level ? $term?->level?->only('id', 'name', 'others') : null,
                                    'academic_year' => $term->academic_year,
                                    'gradeRequest' => $term->gradeRequests->isNotEmpty(),
                                    'subjects' => $term->subjects->map(function ($sub) {
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
                                            'request' => (function () use ($sub) {
                                                $gradeRequest = $sub->gradeRequests()->where('status', 'submitted')->first();

                                                return [
                                                    'id' => $gradeRequest?->id,
                                                    'grade' => $gradeRequest?->grade?->grade,
                                                    'is_failed' => $gradeRequest?->grade?->is_failed,
                                                    'is_incomplete' => $gradeRequest?->grade?->is_incomplete,
                                                    'is_drop' => $gradeRequest?->grade?->is_drop,
                                                    'is_active' => $gradeRequest?->grade?->is_active,
                                                ];
                                            })(),
                                        ];
                                    }),
                                ];
                            }),
                            'requestGrades' => $q->termRecords
                                ->map(function ($term) {
                                    return [
                                        'id' => $term->id,
                                        'term' => $term?->id ? $term?->only('id', 'name') : null,
                                        'level' => $term?->level ? $term?->level->only('id', 'name', 'others') : null,
                                        'academic_year' => $term->academic_year,
                                        'subjects' => $term->requests->flatMap(function ($studentSubject) {
                                            return $studentSubject->subjectRequests->map(function ($subjectRequest) {
                                                return [
                                                    'subject' => [
                                                        'id' => $subjectRequest->subject?->id,
                                                        'name' => $subjectRequest->subject?->name,
                                                        'code' => $subjectRequest->subject?->subject_code,
                                                        'unit' => $subjectRequest->subject?->unit,
                                                    ],
                                                    'grade' => [
                                                        'id' => null,
                                                        'grade' => null,
                                                        'is_failed' => null,
                                                        'is_incomplete' => null,
                                                        'is_drop' => null,
                                                        'is_active' => null,
                                                    ],
                                                ];
                                            });
                                        }),
                                    ];
                                }),

                        ];
                    } : null,
                'resultSearch' => request('findAddress')
                    ? ($location->getFullAddress(request('findAddress')) ?? [])
                    : [],
                'schoolFilter' => $schoolFilter,
                'programFilter' => $programFilter,
                'scholarTypeFilter' => $scholarTypeFilter,
                'statusFilter' => $statusFilter,
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
            $decodedId = Hashids::decode($id)[0] ?? 0;
            $scholar = Scholars::findOrFail($decodedId);

            if ($type == 'personal') {
                $data = $request->validate([
                    'first_name' => 'required|string|max:255',
                    'middle_name' => 'nullable|string|max:255',
                    'last_name' => 'required|string|max:255',
                    'suffix' => 'nullable|string|max:255',
                    'email' => 'required|email|max:255',
                    'contact_no' => 'nullable|string|max:20',
                    'birth_place' => 'nullable|string|max:255',
                    'birth_date' => 'nullable|date',
                    'religion' => 'nullable|string|max:255',
                    'civil_status' => 'nullable|string|max:255',
                    'fulladdress' => 'required',
                    // // Scholarship
                    'program' => 'required',
                    'sub_program' => 'required',
                    'award_year' => 'required',
                    'status' => 'required',
                    'school' => 'required',
                    'course' => 'required',
                    'schoolId' => 'required',

                    // // Guardian
                    'guardian_name' => 'nullable|string|max:255',
                    'guardian_id_no' => 'nullable|string|max:255',
                    'guardian_place_issue' => 'nullable|string|max:255',
                    'guardian_date_issue' => 'nullable|date',
                ]);

                $slice = explode('-', $data['fulladdress']['id']);

                $scholar->update([
                    'program_id' => $data['program']['id'],
                    'type_id' => $data['sub_program']['id'],
                    'award_year' => Carbon::parse($data['award_year'])->format('Y') + 1,
                    'status_id' => $data['status']['id'],
                    '',
                ]);
                $scholar->profile()->updateOrCreate(
                    ['scholar_id' => $scholar->id],
                    [
                        'fname' => $data['first_name'],
                        'mname' => $data['middle_name'] ?? null,
                        'lname' => $data['last_name'],
                        'suffix' => $data['suffix'] ?? null,
                        'email' => $data['email'],

                        'contact_no' => $data['contact_no'] ?? null,
                        'birthplace' => $data['birth_place'] ?? null,
                        'birthdate' => Carbon::parse($data['birth_date'])->setTimezone('Asia/Manila')
                            ->format('m/d/Y'),
                        'religion' => $data['religion'] ?? null,
                        'civil_status' => $data['civil_status'] ?? null,
                    ]
                );

                $scholar->address()->updateOrCreate(
                    ['scholar_id' => $scholar->id],
                    [
                        'address' => $data['address'] ?? null,
                        'barangay_code' => $slice[0] ?? null,
                        'municipality_code' => $slice[1] ?? null,
                        'province_code' => $slice[2] ?? null,
                        'region_code' => $slice[3] ?? null,
                    ]
                );

                $scholar->schoolInfo()->updateOrCreate(
                    [
                        'id' => $data['schoolId'],
                        'scholar_id' => $scholar->id,
                    ],
                    [
                        'campus_id' => $data['school']['id'],
                        'campus_course_id' => $data['course']['id'],
                    ]
                );

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
            if ($type == 'grades') {

                $data = $request->validate([
                    'school' => 'nullable',
                    'course' => 'nullable',
                    'term' => 'required',
                    'year' => 'required',
                    'academic_year' => 'required',
                    'subjects' => 'required',
                    'subjects.*.grade' => 'required',
                    'subjects.*.subject' => 'required',
                ]);

                $termRecord = $scholar->termRecords()->updateOrCreate(
                    [
                        'term_id' => $data['term']['id'],
                        'level_id' => $data['year']['id'],
                        'academic_year' => $data['academic_year'],
                    ],
                    [
                        'scholar_school_id' => $scholar->schoolInfo->first()?->id,
                        'term_type_id' => $scholar->schoolInfo->first()->campus->term?->id ?? null,
                        'level_id' => $data['year']['id'] ?? null,
                        'term_id' => $data['term']['id'] ?? null,
                        'academic_year' => $data['academic_year'] ?? null,
                    ]
                );
                foreach ($data['subjects'] as $key => $value) {
                    $termRecord->subjects()->updateOrCreate(
                        [
                            'subject_id' => $value['subject']['id'],
                            'grade_id' => $value['grade']['id'],
                        ],
                        [
                            'grade_id' => $value['grade']['id'],
                            'remarks' => 'created by system',
                        ]
                    );
                }

                return redirect()->back()->with([
                    'flash' => [
                        'status' => 'success',
                        'title' => 'Grade Saved',
                        'message' => 'Grade record saved successfully.',
                    ],
                ]);
            }
        } catch (\Throwable $th) {
            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Save Failed',
                'message' => 'Missing or invalid required fields. Please check your input and try again.',
            ]);
        }
    }

    public function validate(string $id, string $type, Request $request)
    {
        try {
            if ($type == 'reject') {
                $data = $request->validate([
                    'reason' => 'required|string|max:255',
                ]);

                $requestRecord = StudentSubjectRequest::findOrFail($id);
                $requestRecord->studentSubject->update([
                    'status' => 'rejected',
                    'remarks' => $data['reason'],
                    'updated_by' => Auth::user()->profile->fullname,
                ]);

                return redirect()->back()->with('flash', [
                    'status' => 'success',
                    'title' => 'Request Updated!',
                    'message' => 'The subject request has been successfully updated.',
                ]);
            } else {
                $requestRecord = StudentSubjectRequest::findOrFail($id);
                $studentSubject = $requestRecord->studentSubject;
                $termRecordId = $studentSubject->term_record_id;

                $studentSubject->update([
                    'status' => 'approved',
                    'updated_by' => Auth::user()->profile->fullname,
                ]);

                ScholarSchoolGrades::create([
                    'term_record_id' => $termRecordId,
                    'subject_id' => $requestRecord->subject_id,
                    'grade_id' => null,
                ]);

                return redirect()->back()->with('flash', [
                    'status' => 'success',
                    'title' => 'Request Updated!',
                    'message' => 'The subject request has been successfully updated.',
                ]);
            }
        } catch (\Throwable $th) {
            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Save Failed',
                'message' => 'Missing or invalid required fields. Please check your input and try again.',
            ])->with('');
        }
    }

    public function gradeValidate(string $id, string $type, Request $request)
    {
        try {
            $termRecordId = (int) $id;  // Use the ID directly without decoding

            if ($type == 'reject') {
                $data = $request->validate([
                    'reason' => 'required|string|max:255',
                ]);

                StudentGrade::on('scholars')
                    ->where('term_record_id', $termRecordId)
                    ->where('status', 'submitted')
                    ->update([
                        'status' => 'rejected',
                        'remarks' => $data['reason'],
                    ]);

                return redirect()->back()->with('flash', [
                    'status' => 'success',
                    'title' => 'Grades Rejected!',
                    'message' => 'All grade requests have been successfully rejected.',
                ]);
            } else {
                // Get all grade requests for this term
                $gradeRequests = StudentGradeRequest::on('scholars')
                    ->whereHas('studentGrade', function ($q) use ($termRecordId) {
                        $q->where('term_record_id', $termRecordId)
                            ->where('status', 'submitted');
                    })
                    ->get();

                foreach ($gradeRequests as $gradeRequest) {
                    // Update scholar_school_grades (on default pgsql connection)
                    $updated = ScholarSchoolGrades::query()
                        ->where('term_record_id', $termRecordId)
                        ->where('subject_id', $gradeRequest->subject_id)
                        ->update([
                            'grade_id' => $gradeRequest->grades_id,
                        ]);
                }

                // Update all student_grades for this term to validated
                $updated = StudentGrade::on('scholars')
                    ->where('term_record_id', $termRecordId)
                    ->where('status', 'submitted')
                    ->update([
                        'status' => 'validated',
                    ]);

                return redirect()->back()->with('flash', [
                    'status' => 'success',
                    'title' => 'Grades Validated!',
                    'message' => 'All grade requests have been successfully validated and updated.',
                ]);
            }
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

        $url = 'https://portal7.science-scholarships.ph/activation?token='.$activation;
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
                'region_code' => LocationRegions::firstWhere('name', $data['region'])?->code,
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
                'region_code' => LocationRegions::firstWhere('name', $data['region'])?->code,
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
            'title' => 'Action Completed',
            'message' => $type === 'accept'
                ? 'The change request has been approved.'
                : 'The change request has been declined.',
        ]);
    }

    public function landbankRequest(string $type, Request $request)
    {
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
            $scholar->landbankRequest()->update([
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
            $scholar->landbankRequest()->update([
                'status' => 'rejected',
                'reviewed_at' => Carbon::now(),
                'reviewed_by' => Auth::user()->profile->fullname,
                'rejection_reason' => $data['reject'],
            ]);
        }

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title' => 'Action Completed',
            'message' => $type === 'accept'
                ? 'The change request has been approved.'
                : 'The change request has been declined.',
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
