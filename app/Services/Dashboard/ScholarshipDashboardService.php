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
class ScholarshipDashboardService
{
    public function render(Request $request, $user, SystemPermissions $permissions)
    {
            $asOfMonth = $request->input('as_of_month');
            if (! is_string($asOfMonth) || ! preg_match('/^\d{4}-\d{2}$/', $asOfMonth)) {
                $asOfMonth = now()->format('Y-m');
            }

            $asOfDate = Carbon::createFromFormat('Y-m', $asOfMonth)->endOfMonth();
            $asOfYear = (int) $asOfDate->year;

            $asOfScholars = Scholars::with([
                'program:id,name',
                'profile:sex,scholar_id',
            ])
                ->whereNotNull('activated_at')
                ->whereNotNull('award_year')
                ->where('award_year', '<=', $asOfYear)
                ->get();

            $scholars = Scholars::with([
                'program:id,name',
                'profile:sex,scholar_id',
            ])
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

            $timelineTotal = $asOfScholars
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
                                    $asOfScholars
                                        ->filter(fn ($scholar) => optional($scholar->profile)->sex === 'F')
                                        ->count(),
                                    $asOfScholars
                                        ->filter(fn ($scholar) => optional($scholar->profile)->sex === 'M')
                                        ->count(),
                                ],
                            ],
                        ],
                    ],
                ],
                'asOf' => [
                    'month' => $asOfMonth,
                    'label' => $asOfDate->format('F Y'),
                    'year' => $asOfYear,
                    'active' => Scholars::whereNotIn('academic_status', [
                            'GRADUATED',
                            'TERMINATED',
                            'WITHDRAWN',
                        ])
                        ->whereNotNull('activated_at')
                        ->whereNotNull('award_year')
                        ->where('award_year', '<=', $asOfYear)
                        ->count(),
                    'undergraduate' => Scholars::where('type_id', 28)
                        ->whereNotNull('activated_at')
                        ->whereNotNull('award_year')
                        ->where('award_year', '<=', $asOfYear)
                        ->count(),
                    'jlss' => Scholars::where('type_id', 29)
                        ->whereNotNull('activated_at')
                        ->whereNotNull('award_year')
                        ->where('award_year', '<=', $asOfYear)
                        ->count(),
                    'graduated' => Scholars::where('academic_status', 'GRADUATED')
                        ->whereNotNull('activated_at')
                        ->whereNotNull('award_year')
                        ->where('award_year', '<=', $asOfYear)
                        ->count(),
                ],
                'card' => [
                    'active' => Scholars::whereNotIn('academic_status', [
                        'GRADUATED',
                        'TERMINATED',
                        'WITHDRAWN',
                    ])->count(),
                    'undergraduate' => Scholars::where('type_id', 28)->count(),
                    'jlss' => Scholars::where('type_id', 29)->count(),
                    'graduated' => Scholars::where('academic_status', 'GRADUATED')->count(),
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
