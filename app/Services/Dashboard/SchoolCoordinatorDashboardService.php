<?php

namespace App\Services\Dashboard;

use App\Models\Batches;
use App\Models\LocationRegions;
use App\Models\ScholarAcademicHistorySubmission;
use App\Models\ScholarProfiles;
use App\Models\Scholars;
use App\Models\ScholarTerm;
use App\Models\SchoolCampusCourses;
use App\Models\SchoolCampuses;
use App\Models\studentLandbankRequest;
use App\Models\StudentProfileRequest;
use App\Support\SystemPermissions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
class SchoolCoordinatorDashboardService
{
    public function render(Request $request, $user, SystemPermissions $permissions)
    {
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
    }
}

