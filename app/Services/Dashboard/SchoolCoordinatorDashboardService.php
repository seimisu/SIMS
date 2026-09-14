<?php

namespace App\Services\Dashboard;

use App\Models\Scholars;
use App\Models\SchoolCampusCourses;
use App\Models\SchoolCampuses;
use App\Support\SystemPermissions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SchoolCoordinatorDashboardService
{
    public function render(Request $request, $user, SystemPermissions $permissions)
    {
        $campusId = $user->school_id;
        $ranges = collect();
        $firstYear = Scholars::min('award_year');
        $lastYear = Carbon::now()->year;
        $start = $firstYear ?? $lastYear;

        while ($start <= $lastYear) {
            $end = min($start + 10, $lastYear);

            $ranges->push([
                'name' => "{$start}-{$end}",
                'start' => $start,
                'end' => $end,
            ]);

            $start = $end + 1;
        }

        $campus = SchoolCampuses::select('id', 'school_id', 'generated_name', 'grading_id', 'term_id')->find($campusId);

        $scholars = Scholars::with([
            'program:id,name',
            'profile:sex,scholar_id',
            'schoolInfo',
        ])
            ->whereHas('schoolInfo', function ($schoolInfo) use ($campusId) {
                $schoolInfo->where('campus_id', $campusId);
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

        $programTotals = $scholars
            ->groupBy(fn ($s) => $s->program->name)
            ->map(function ($rows, $program) {
                return [
                    'name' => $program,
                    'data' => $rows->count(),
                ];
            });

        $timelineTotal = collect(['MERIT', 'RA 7687', 'RA 10612'])
            ->map(fn ($program) => $programTotals->firstWhere('name', $program) ?? [
                'name' => $program,
                'data' => 0,
            ]);

        $courseTreemap = SchoolCampusCourses::query()
            ->where('is_delete', false)
            ->where('campus_id', $campusId)
            ->with(['course:id,name,abbreviation'])
            ->withCount([
                'scholarCourse as scholar_count' => function ($query) {
                    $query->select(DB::raw('COUNT(DISTINCT scholar_id)'))->where('is_transfer', false);
                },
            ])
            ->get();
        $totalCourse = $courseTreemap->sum('scholar_count');

        $genderCount = $scholars->whereIn('profile.sex', ['F', 'M'])->count();
        $maleCount = $scholars->where('profile.sex', 'M')->count();
        $femaleCount = $scholars->where('profile.sex', 'F')->count();

        return Inertia::render('Web/dashboardPage', [
            'dashboardType' => $permissions->dashboardType($user),
            'schoolDetails' => SchoolCampuses::where(['id' => $campusId])
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
                                'x' => Str::upper($course->course->abbreviation ?? $course->course->name ?? $course->name),
                                'y' => $course->scholar_count,
                            ];
                        })->values(),
                    ],
                ],
                'table' => $courseTreemap->sortByDesc('scholar_count')->map(function ($course) use ($totalCourse) {
                    return [
                        'name' => Str::upper($course->course->name ?? $course->name),
                        'percent' => $totalCourse > 0
                            ? ($course->scholar_count / $totalCourse) * 100
                            : 0,
                        'total' => $course->scholar_count,
                    ];
                })->values(),
                'total' => $totalCourse,
            ],
            'gender' => [
                'donut' => [
                    $maleCount,
                    $femaleCount,
                ],
                'pFemale' => $genderCount > 0 ? round($femaleCount / $genderCount * 100, 1) : 0,
                'pMale' => $genderCount > 0 ? round($maleCount / $genderCount * 100, 1) : 0,
                'majority' => $femaleCount > $maleCount ? 'Female' : 'Male',
                'total' => $scholars->count(),
            ],
            'semesterDate' => $campus?->semesters()
                ->where('is_delete', false)
                ->where('is_active', true)
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->get()
                ->map(fn ($semester) => [
                    'name' => $semester->semester->name,
                    'startDate' => Carbon::parse($semester->start_date)->setTimezone('Asia/Manila')->format('M Y'),
                    'endDate' => Carbon::parse($semester->end_date)->setTimezone('Asia/Manila')->format('M Y'),
                    'submissionDate' => Carbon::parse($semester->submission_date)->setTimezone('Asia/Manila')->format('M d, Y'),
                ])->first(),
            'events' => [],
            'options' => [
                'dateRange' => $ranges,
            ],
            'card' => [
                'active' => Scholars::whereIn('academic_status', [
                    'NEW',
                    'ONGOING',
                ])
                    ->whereHas('schoolInfo', function ($schoolInfo) use ($campusId) {
                        $schoolInfo->where('campus_id', $campusId);
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
                'graduated' => Scholars::where('academic_status', 'GRADUATED')
                    ->whereHas('schoolInfo', function ($schoolInfo) use ($campusId) {
                        $schoolInfo->where('campus_id', $campusId);
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
                'issue' => Scholars::whereNotIn('academic_status', [
                    'GRADUATED',
                    'NEW',
                    'ONGOING',
                    'TERMINATED',

                ])
                    ->whereHas('schoolInfo', function ($schoolInfo) use ($campusId) {
                        $schoolInfo->where('campus_id', $campusId);
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
                'terminated' => Scholars::where('academic_status', 'TERMINATED')
                    ->whereHas('schoolInfo', function ($schoolInfo) use ($campusId) {
                        $schoolInfo->where('campus_id', $campusId);
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
            ],
        ]);
    }
}
