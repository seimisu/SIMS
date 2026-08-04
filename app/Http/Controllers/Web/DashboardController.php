<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Batches;
use App\Models\LocationRegions;
use App\Models\ScholarAcademicHistorySubmission;
use App\Models\ScholarProfiles;
use App\Models\Scholars;
use App\Models\SchoolCampusCourses;
use App\Models\SchoolCampuses;
use App\Models\studentLandbankRequest;
use App\Models\StudentProfileRequest;
use App\Models\StudentSubject;
use App\Support\SystemPermissions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $permissions = app(SystemPermissions::class);

        $regionCode = $user->profile->agency->region_code;
        $currentYear = Carbon::now()->year;

        // $campuses = SchoolCampuses::select('id', 'generated_name', 'school_id', 'name')
        //     ->where('is_delete', false)
        //     ->when($permissions->shouldScopeToRegion($user), function ($q) use ($permissions, $user) {
        //         $q->whereHas('address', fn ($address) => $address->where('region_code', $permissions->regionCodeFor($user)));
        //     })
        //     ->with([
        //         'school' => fn ($q) => $q
        //             ->select('id', 'shortcut')
        //             ->where('is_delete', false),
        //         'address',
        //         'semesters' => fn ($q) => $q
        //             ->select('id', 'semester_id', 'campus_id', 'start_date', 'end_date', 'submission_date')
        //             ->whereDate('start_date', '<=', now())
        //             ->whereDate('end_date', '>=', now()),
        //     ])
        //     ->get();

        // // Duplicate for testing
        // $campuses = $campuses->merge($campuses);
        if ($permissions->isRegionalRole($user)) {
            $scholars = Scholars::with([
                'program:id,name',
                'profile:sex,scholar_id',
                'status:id,name',
                'schoolInfo.campus:id,generated_name',
            ])
                ->whereHas(
                    'schoolInfo.campus.address',
                    fn ($q) => $q->where('region_code', $regionCode)
                )
                ->get();
            $payrollRegion = $permissions->agencyNameFor($user) ?? '';
            $payrollBatches = Batches::with(['latestLog', 'term:id,name'])
                ->whereNull('deleted_at')
                ->where('region', $payrollRegion)
                ->withCount('recipients')
                ->latest('updated_at')
                ->get();
            $payrollStatus = fn ($batch) => $batch->status ?: ($batch->latestLog?->status ?: 'draft');
            $payrollLabels = [
                'draft' => 'Draft',
                'submitted_payroll' => 'Submitted',
                'rejected_payroll' => 'Returned',
                'approved_payroll' => 'Approved',
            ];
            $payrollSummary = collect(['draft', 'submitted_payroll', 'rejected_payroll', 'approved_payroll'])
                ->mapWithKeys(fn ($status) => [$status => $payrollBatches->filter(fn ($batch) => $payrollStatus($batch) === $status)->count()]);
            $payrollQueue = $payrollBatches
                ->filter(fn ($batch) => in_array($payrollStatus($batch), ['draft', 'rejected_payroll'], true))
                ->take(6)
                ->map(fn ($batch) => [
                    'id' => $batch->id,
                    'name' => $batch->name,
                    'term' => $batch->academic_term,
                    'school_year' => $batch->school_year,
                    'status' => $payrollStatus($batch),
                    'status_label' => $payrollLabels[$payrollStatus($batch)] ?? Str::headline($payrollStatus($batch)),
                    'recipients' => $batch->recipients_count,
                    'remarks' => $batch->latestLog?->remarks,
                    'updated_at' => $batch->updated_at ? Carbon::parse($batch->updated_at)->format('M d, Y') : null,
                ])
                ->values();
            $recentPayrollActivity = $payrollBatches
                ->filter(fn ($batch) => $batch->latestLog)
                ->sortByDesc(fn ($batch) => $batch->latestLog->created_at)
                ->take(5)
                ->map(fn ($batch) => [
                    'name' => $batch->name,
                    'status' => $payrollStatus($batch),
                    'status_label' => $payrollLabels[$payrollStatus($batch)] ?? Str::headline($payrollStatus($batch)),
                    'actor' => $batch->latestLog?->action_by,
                    'date' => $batch->latestLog?->created_at ? Carbon::parse($batch->latestLog->created_at)->format('M d, Y') : null,
                ])
                ->values();
            $statusSummary = $scholars
                ->groupBy(fn ($scholar) => $scholar->status?->name ?? 'No status')
                ->map(fn ($rows, $name) => [
                    'name' => $name,
                    'count' => $rows->count(),
                ])
                ->sortByDesc('count')
                ->values();
            $scholarCountsBySchool = $scholars
                ->flatMap(fn ($scholar) => $scholar->schoolInfo)
                ->filter(fn ($schoolInfo) => $schoolInfo->campus)
                ->groupBy(fn ($schoolInfo) => $schoolInfo->campus->generated_name)
                ->map(fn ($rows) => $rows->pluck('scholar_id')->unique()->count());
            $schoolDistribution = SchoolCampuses::select('id', 'generated_name')
                ->where('is_delete', false)
                ->when($permissions->shouldScopeToRegion($user), function ($q) use ($permissions, $user) {
                    $q->whereHas('address', fn ($address) => $address->where('region_code', $permissions->regionCodeFor($user)));
                })
                ->orderBy('generated_name')
                ->get()
                ->map(fn ($campus) => [
                    'name' => $campus->generated_name,
                    'count' => $scholarCountsBySchool->get($campus->generated_name, 0),
                ])
                ->values();
            $regionalSpas = $scholars->pluck('spas_no')->filter()->values();
            $pendingSubmissions = [
                'grades' => StudentSubject::where('status', 'submitted')
                    ->whereIn('spas_no', $regionalSpas)
                    ->count(),
                'history' => ScholarAcademicHistorySubmission::where('status', 'submitted')
                    ->whereIn('spas_no', $regionalSpas)
                    ->count(),
                'profile' => StudentProfileRequest::where('status', 'pending')
                    ->whereIn('spas_no', $regionalSpas)
                    ->count(),
                'landbank' => studentLandbankRequest::where('status', 'pending')
                    ->whereIn('spas_no', $regionalSpas)
                    ->count(),
            ];

            $categories = $scholars
                ->pluck('award_year')
                ->flatten()
                ->filter()
                ->unique()
                ->sort()
                ->values();

            $series = $scholars
                ->groupBy(fn ($s) => $s->program->name)
                ->map(function ($rows, $program) use ($categories) {
                    $data = collect($categories)->map(function ($year) use ($rows) {
                        return $rows->filter(
                            fn ($s) => $s->award_year == $year
                        )->count();
                    })->toArray();

                    return [
                        'name' => $program,
                        'data' => $data,
                    ];
                })
                ->values()
                ->toArray();

            $timelineTotal = $scholars
                ->groupBy(fn ($s) => $s->program->name)
                ->map(function ($rows, $program) {

                    return [
                        'name' => $program,
                        'data' => $rows->count(),
                    ];
                })
                ->values()
                ->toArray();

            return Inertia::render('Web/dashboardPage', [
                'dashboardType' => $permissions->dashboardType($user),
                'campus_cnt' => SchoolCampuses::where('is_delete', false)
                    ->when($permissions->shouldScopeToRegion($user), function ($q) use ($permissions, $user) {
                        $q->whereHas('address', fn ($address) => $address->where('region_code', $permissions->regionCodeFor($user)));
                    })->count(),
                'campuses_details' => SchoolCampuses::where('is_delete', false)
                    ->when($permissions->shouldScopeToRegion($user), function ($q) use ($permissions, $user) {
                        $q->whereHas('address', fn ($address) => $address->where('region_code', $permissions->regionCodeFor($user)));
                    })->exists() ? SchoolCampuses::select('id', 'generated_name', 'school_id', 'name')->where('is_delete', false)
                    ->when($permissions->shouldScopeToRegion($user), function ($q) use ($permissions, $user) {
                        $q->whereHas('address', fn ($address) => $address->where('region_code', $permissions->regionCodeFor($user)));
                    })
                    ->with([
                        'school' => fn ($q) => $q
                            ->select('id', 'shortcut')
                            ->where('is_delete', false),
                        'address',
                        'semesters' => fn ($q) => $q
                            ->select('id', 'semester_id', 'campus_id', 'start_date', 'end_date', 'submission_date')
                            ->whereDate('start_date', '<=', now())
                            ->whereDate('end_date', '>=', now()),
                    ])
                    ->withCount('courses')
                    ->get()
                    ->map(function ($campus) {
                        return [
                            'id' => $campus->id,
                            'generated_name' => $campus->generated_name,
                            'school_shortcut' => $campus->school
                                ? (
                                    $campus->name
                                    ? $campus->school->shortcut.'-'.$campus->name
                                    : $campus->school->shortcut.'-'.$campus->address?->municipality_array['name']
                                )
                                : null,
                            'program_cnt' => $campus->courses_count,
                            'grading_status' => $campus->grades()->exists(),
                            'semesters' => $campus->semesters->isNotEmpty()
                                ? [
                                    'start_date' => Carbon::parse($campus->semesters[0]->start_date)->format('M Y'),
                                    'end_date' => Carbon::parse($campus->semesters[0]->end_date)->format('M Y'),
                                    'type' => $campus->semesters[0]->semester_array,
                                    'submission_date' => Carbon::parse($campus->semesters[0]->submission_date)->format('M d, Y'),
                                ]
                                : [],

                        ];
                    }) : null,
                'card' => [
                    'Ucnt' => $scholars->where('type_id', 28)->count(),
                    'UTotalcnt' => $scholars
                        ->where('type_id', 28)
                        ->where('award_year', $currentYear)
                        ->count(),
                    'JTotalcnt' => $scholars->where('type_id', 29)
                        ->where('award_year', $currentYear)
                        ->count(),

                    'Jcnt' => $scholars->where('type_id', 29)->count(),
                    'totalyear' => $scholars
                        ->where('award_year', $currentYear)
                        ->count(),
                    'total' => $scholars->count(),
                ],
                'regionalInsights' => [
                    'payrollSummary' => $payrollSummary,
                    'payrollQueue' => $payrollQueue,
                    'recentPayrollActivity' => $recentPayrollActivity,
                    'statusSummary' => $statusSummary,
                    'schoolDistribution' => $schoolDistribution,
                    'pendingSubmissions' => $pendingSubmissions,
                    'activeCampuses' => SchoolCampuses::where('is_delete', false)
                        ->when($permissions->shouldScopeToRegion($user), function ($q) use ($permissions, $user) {
                            $q->whereHas('address', fn ($address) => $address->where('region_code', $permissions->regionCodeFor($user)));
                        })->count(),
                    'campusesWithActiveTerm' => SchoolCampuses::where('is_delete', false)
                        ->when($permissions->shouldScopeToRegion($user), function ($q) use ($permissions, $user) {
                            $q->whereHas('address', fn ($address) => $address->where('region_code', $permissions->regionCodeFor($user)));
                        })
                        ->whereHas('semesters', fn ($q) => $q->whereDate('start_date', '<=', now())->whereDate('end_date', '>=', now()))
                        ->count(),
                ],
                'timeline' => [
                    'categories' => $categories,
                    'series' => $series,
                    'timelineTotal' => $timelineTotal,
                ],

                'gender' => [
                    'series' => $scholars
                        ->groupBy(fn ($s) => $s->profile?->sex)
                        ->map(fn ($rows) => $rows->count())
                        ->values()
                        ->toArray(),
                    'result' => $scholars->groupBy(fn ($s) => $s->profile?->sex)->map(function ($rows, $gender) {
                        return ['sex' => $gender, 'total' => $rows->count()];
                    })->values()->toArray(),
                ],
            ]);
        } elseif ($permissions->isSchoolCoordinator($user)) {
            $ranges = collect();
            $firstYear = Scholars::min('award_year');
            $lastYear = Carbon::now()->year;
            $start = $firstYear;

            while ($start <= $lastYear) {
                $end = min($start + 10, $lastYear);

                $ranges->push([
                    'name' => "{$start}-{$end}",
                    'start' => $start,
                    'end' => $end,
                ]);

                $start = $end + 1;
            }

            $scholars = Scholars::with([
                'program:id,name',
                'profile:sex,scholar_id',
                'schoolInfo',
            ])
                ->whereHas('schoolInfo', function ($schoolInfo) {
                    $schoolInfo->where('campus_id', Auth::user()->school_id);
                })
                ->when(
                    $request->input('range'),
                    function ($query) use ($request) {

                        $query->whereBetween('award_year', [
                            $request->input('range')['start'],
                            $request->input('range')['end'],
                        ]);
                    },
                    function ($query) use ($ranges) {

                        $query->whereBetween('award_year', [
                            $ranges->last()['start'],
                            $ranges->last()['end'],
                        ]);
                    }
                )

                ->orderByDesc('program_id')
                ->get();

            $categories = $scholars
                ->pluck('award_year')
                ->flatten()
                ->filter()
                ->unique()
                ->sort()
                ->values();

            $series = $scholars
                ->groupBy(fn ($s) => $s->program->name)
                ->sortKeys()
                ->map(function ($rows, $program) use ($categories) {
                    $data = collect($categories)->map(function ($year) use ($rows) {
                        return $rows->where('award_year', $year)->count();
                    })->toArray();

                    return [
                        'name' => $program,
                        'data' => $data,
                    ];
                })
                ->values()
                ->toArray();

            $timelineTotal = $scholars
                ->groupBy(fn ($s) => $s->program->name)
                ->map(function ($rows, $program) {
                    return [
                        'name' => $program,
                        'data' => $rows->count(),
                    ];
                })
                ->values();

            $regions = LocationRegions::where('is_active', true)
                ->with('scholars.profile')
                ->get();

            $regionWithSex = collect(['F', 'M'])->map(function ($sex) use ($regions) {
                return [
                    'name' => $sex === 'F' ? 'Female' : 'Male',
                    'data' => $regions->map(function ($region) use ($sex) {
                        return [
                            'x' => $region->region,
                            'y' => $region->scholars
                                ->filter(fn ($scholar) => optional($scholar->profile)->sex === $sex)
                                ->count(),
                        ];
                    })->values(),
                ];
            })->values();

            $schoolTreemap = SchoolCampuses::withCount('scholarCampus')
                ->get()
                ->values();
            $totalSchool = $schoolTreemap->map(function ($campus) {

                return [
                    'x' => Str::upper($campus->name == null ? $campus->school->shortcut.'-'.$campus->address->municipality_array['name'] : $campus->school->shortcut.'-'.$campus->name),
                    'y' => $campus->scholar_campus_count,
                ];
            })->sum('y');

            $courseTreemap = SchoolCampusCourses::where('is_delete', false)
                ->withCount('scholarCourse')
                ->get();
            $totalCourse = $courseTreemap->sum('scholar_course_count');

            return Inertia::render('Web/dashboardPage', [
                'dashboardType' => $permissions->dashboardType($user),
                'schoolDetails' => SchoolCampuses::where(['id' => Auth::user()->school_id])
                    ->with(['address'])
                    ->first(),

                'timeline' => [
                    'categories' => $categories,
                    'series' => $series,
                    'programs' => $timelineTotal,
                    'programSeries' => $timelineTotal->pluck('data')->toArray(),
                ],
                'course' => [
                    'series' => [
                        [
                            'data' => $courseTreemap->map(function ($course) {

                                return [
                                    'x' => Str::upper($course->course->name ?? $course->course->abbreviation ?? $course->name),
                                    'y' => $course->scholar_course_count,
                                ];
                            })->values(),
                        ],
                    ],
                    'table' => $courseTreemap->where('campus_id', Auth::user()->school_id)->sortByDesc('scholar_course_count')->map(function ($course) use ($totalCourse) {
                        return [
                            'name' => Str::upper($course->course->name ?? $course->name),
                            'percent' => $totalCourse > 0
                                ? ($course->scholar_course_count / $totalCourse) * 100
                                : 0,
                            'total' => $course->scholar_course_count,
                        ];
                    })->values(),
                    'total' => $totalCourse,
                ],
                'gender' => [
                    'series' => $regionWithSex,
                    'bar' => [
                        'series' => [
                            [
                                'name' => 'Scholars',
                                'data' => [
                                    ScholarProfiles::where('sex', 'F')->count(),
                                    ScholarProfiles::where('sex', 'M')->count(),
                                ],
                            ],
                        ],
                    ],
                ],
                'options' => [
                    'dateRange' => $ranges,
                ],
                'card' => [
                    'active' => Scholars::whereNotIn('academic_status', [
                        'GRADUATED',
                        'TERMINATED',
                        'WITHDRAWN',
                    ])
                        ->whereHas('schoolInfo', function ($schoolInfo) {
                            $schoolInfo->where('campus_id', Auth::user()->school_id);
                        })
                        ->when($request->filled('filter'), function ($query) use ($request) {
                            switch ($request->input('filter')) {
                                case 'year':
                                    $query->whereYear('activated_at', now()->year);
                                    break;

                                case 'month':
                                    $query->whereMonth('activated_at', now()->month)
                                        ->whereYear('activated_at', now()->year);
                                    break;

                                    // case 'week':
                                    //     $query->whereBetween('activated_at', [
                                    //         now()->startOfWeek(),
                                    //         now()->endOfWeek(),
                                    //     ]);
                                    //     break;
                            }
                        })->count(),
                    'graduated' => Scholars::where('academic_status', 'GRADUATED')->when($request->filled('filter'), function ($query) use ($request) {
                        switch ($request->input('filter')) {
                            case 'year':
                                $query->whereYear('activated_at', now()->year);
                                break;

                            case 'month':
                                $query->whereMonth('activated_at', now()->month)
                                    ->whereYear('activated_at', now()->year);
                                break;

                                // case 'week':
                                //     $query->whereBetween('activated_at', [
                                //         now()->startOfWeek(),
                                //         now()->endOfWeek(),
                                //     ]);
                                //     break;
                        }
                    })->count(),
                    'issue' => Scholars::whereNotIn('academic_status', [
                        'GRADUATED',
                        'NEW',
                        'ONGOING',

                    ])->when($request->filled('filter'), function ($query) use ($request) {
                        switch ($request->input('filter')) {
                            case 'year':
                                $query->whereYear('activated_at', now()->year);
                                break;

                            case 'month':
                                $query->whereMonth('activated_at', now()->month)
                                    ->whereYear('activated_at', now()->year);
                                break;

                                // case 'week':
                                //     $query->whereBetween('activated_at', [
                                //         now()->startOfWeek(),
                                //         now()->endOfWeek(),
                                //     ]);
                                //     break;
                        }
                    })->count(),
                    'terminated' => Scholars::where('academic_status', 'TERMINATED')->when($request->filled('filter'), function ($query) use ($request) {
                        switch ($request->input('filter')) {
                            case 'year':
                                $query->whereYear('activated_at', now()->year);
                                break;

                            case 'month':
                                $query->whereMonth('activated_at', now()->month)
                                    ->whereYear('activated_at', now()->year);
                                break;

                                // case 'week':
                                //     $query->whereBetween('activated_at', [
                                //         now()->startOfWeek(),
                                //         now()->endOfWeek(),
                                //     ]);
                                //     break;
                        }
                    })->count(),
                ],
            ]);
        } else {
            $ranges = collect();
            $firstYear = Scholars::min('award_year');
            $lastYear = Carbon::now()->year;
            $start = $firstYear;

            while ($start <= $lastYear) {
                $end = min($start + 10, $lastYear);

                $ranges->push([
                    'name' => "{$start}-{$end}",
                    'start' => $start,
                    'end' => $end,
                ]);

                $start = $end + 1;
            }

            $scholars = Scholars::with([
                'program:id,name',
                'profile:sex,scholar_id',
            ])
                ->when(
                    $request->input('range'),
                    function ($query) use ($request) {

                        $query->whereBetween('award_year', [
                            $request->input('range')['start'],
                            $request->input('range')['end'],
                        ]);
                    },
                    function ($query) use ($ranges) {

                        $query->whereBetween('award_year', [
                            $ranges->last()['start'],
                            $ranges->last()['end'],
                        ]);
                    }
                )

                ->orderByDesc('program_id')
                ->get();

            $categories = $scholars
                ->pluck('award_year')
                ->flatten()
                ->filter()
                ->unique()
                ->sort()
                ->values();

            $series = $scholars
                ->groupBy(fn ($s) => $s->program->name)
                ->sortKeys()
                ->map(function ($rows, $program) use ($categories) {
                    $data = collect($categories)->map(function ($year) use ($rows) {
                        return $rows->where('award_year', $year)->count();
                    })->toArray();

                    return [
                        'name' => $program,
                        'data' => $data,
                    ];
                })
                ->values()
                ->toArray();

            $timelineTotal = $scholars
                ->groupBy(fn ($s) => $s->program->name)
                ->map(function ($rows, $program) {
                    return [
                        'name' => $program,
                        'data' => $rows->count(),
                    ];
                })
                ->values();

            $regions = LocationRegions::where('is_active', true)
                ->with('scholars.profile')
                ->get();

            $regionWithSex = collect(['F', 'M'])->map(function ($sex) use ($regions) {
                return [
                    'name' => $sex === 'F' ? 'Female' : 'Male',
                    'data' => $regions->map(function ($region) use ($sex) {
                        return [
                            'x' => $region->region,
                            'y' => $region->scholars
                                ->filter(fn ($scholar) => optional($scholar->profile)->sex === $sex)
                                ->count(),
                        ];
                    })->values(),
                ];
            })->values();

            $schoolTreemap = SchoolCampuses::withCount('scholarCampus')
                ->get()
                ->values();
            $totalSchool = $schoolTreemap->map(function ($campus) {

                return [
                    'x' => Str::upper($campus->name == null ? $campus->school->shortcut.'-'.$campus->address->municipality_array['name'] : $campus->school->shortcut.'-'.$campus->name),
                    'y' => $campus->scholar_campus_count,
                ];
            })->sum('y');

            $courseTreemap = SchoolCampusCourses::where('is_delete', false)
                ->withCount('scholarCourse')
                ->get();
            $totalCourse = $courseTreemap->sum('scholar_course_count');

            return Inertia::render('Web/dashboardPage', [
                'dashboardType' => $permissions->dashboardType($user),
                'timeline' => [
                    'categories' => $categories,
                    'series' => $series,
                    'programs' => $timelineTotal,
                    'programSeries' => $timelineTotal->pluck('data')->toArray(),
                ],
                'school' => [
                    'series' => [
                        [
                            'data' => $schoolTreemap->map(function ($campus) {

                                return [
                                    'x' => Str::upper($campus->name == null ? $campus->school->shortcut.'-'.$campus->address->municipality_array['name'] : $campus->school->shortcut.'-'.$campus->name),
                                    'y' => $campus->scholar_campus_count,
                                ];
                            }),
                        ],
                    ],
                    'table' => $schoolTreemap->map(function ($campus) use ($totalSchool) {

                        return [
                            'name' => $campus->generated_name,
                            'region' => Str::upper($campus->agency->slug),
                            'percent' => $campus->scholar_campus_count / $totalSchool * 100,
                            'total' => $campus->scholar_campus_count,
                        ];
                    }),
                    'total' => $totalSchool,
                ],
                'course' => [
                    'series' => [
                        [
                            'data' => $courseTreemap->map(function ($course) {

                                return [
                                    'x' => Str::upper($course->course->name ?? $course->course->abbreviation ?? $course->name),
                                    'y' => $course->scholar_course_count,
                                ];
                            })->values(),
                        ],
                    ],

                    'table' => $courseTreemap->map(function ($course) use ($totalCourse) {
                        return [
                            'name' => Str::upper($course->course->name ?? $course->name),
                            'percent' => $totalCourse > 0
                                ? ($course->scholar_course_count / $totalCourse) * 100
                                : 0,
                            'total' => $course->scholar_course_count,
                        ];
                    })->values(),

                    'total' => $totalCourse,
                ],
                'gender' => [
                    'series' => $regionWithSex,
                    'bar' => [
                        'series' => [
                            [
                                'name' => 'Scholars',
                                'data' => [
                                    ScholarProfiles::where('sex', 'F')->count(),
                                    ScholarProfiles::where('sex', 'M')->count(),
                                ],
                            ],
                        ],
                    ],
                ],
                'options' => [
                    'dateRange' => $ranges,
                ],
                'card' => [
                    'active' => Scholars::whereNotIn('academic_status', [
                        'GRADUATED',
                        'TERMINATED',
                        'WITHDRAWN',
                    ])->when($request->filled('filter'), function ($query) use ($request) {
                        switch ($request->input('filter')) {
                            case 'year':
                                $query->whereYear('activated_at', now()->year);
                                break;

                            case 'month':
                                $query->whereMonth('activated_at', now()->month)
                                    ->whereYear('activated_at', now()->year);
                                break;

                                // case 'week':
                                //     $query->whereBetween('activated_at', [
                                //         now()->startOfWeek(),
                                //         now()->endOfWeek(),
                                //     ]);
                                //     break;
                        }
                    })->count(),
                    'undergraduate' => Scholars::where('type_id', 28)->count(),
                    'jlss' => Scholars::where('type_id', 29)->count(),
                    'graduated' => Scholars::where('academic_status', 'GRADUATED')->when($request->filled('filter'), function ($query) use ($request) {
                        switch ($request->input('filter')) {
                            case 'year':
                                $query->whereYear('activated_at', now()->year);
                                break;

                            case 'month':
                                $query->whereMonth('activated_at', now()->month)
                                    ->whereYear('activated_at', now()->year);
                                break;

                                // case 'week':
                                //     $query->whereBetween('activated_at', [
                                //         now()->startOfWeek(),
                                //         now()->endOfWeek(),
                                //     ]);
                                //     break;
                        }
                    })->count(),
                    'issue' => Scholars::whereNotIn('academic_status', [
                        'GRADUATED',
                        'NEW',
                        'ONGOING',

                    ])->when($request->filled('filter'), function ($query) use ($request) {
                        switch ($request->input('filter')) {
                            case 'year':
                                $query->whereYear('activated_at', now()->year);
                                break;

                            case 'month':
                                $query->whereMonth('activated_at', now()->month)
                                    ->whereYear('activated_at', now()->year);
                                break;

                                // case 'week':
                                //     $query->whereBetween('activated_at', [
                                //         now()->startOfWeek(),
                                //         now()->endOfWeek(),
                                //     ]);
                                //     break;
                        }
                    })->count(),
                    'terminated' => Scholars::where('academic_status', 'TERMINATED')->when($request->filled('filter'), function ($query) use ($request) {
                        switch ($request->input('filter')) {
                            case 'year':
                                $query->whereYear('activated_at', now()->year);
                                break;

                            case 'month':
                                $query->whereMonth('activated_at', now()->month)
                                    ->whereYear('activated_at', now()->year);
                                break;

                                // case 'week':
                                //     $query->whereBetween('activated_at', [
                                //         now()->startOfWeek(),
                                //         now()->endOfWeek(),
                                //     ]);
                                //     break;
                        }
                    })->count(),
                ],
            ]);
        }
    }
}
