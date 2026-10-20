<?php

namespace App\Services\Dashboard;

use App\Models\LocationRegions;
use App\Models\Scholars;
use App\Support\SystemPermissions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ScholarshipDashboardService
{
    public function render(Request $request, $user, SystemPermissions $permissions)
    {
        $asOfMonth = $request->input('as_of_month');
        if (! is_string($asOfMonth) || ! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $asOfMonth)) {
            $asOfMonth = now()->format('Y-m');
        }
        $asOfDate = Carbon::createFromFormat('!Y-m', $asOfMonth)->endOfMonth();
        $scholars = Scholars::with([
            'program:id,name',
            'profile:sex,scholar_id',
            'status:id,name',
            'schoolInfo.campus:id,generated_name',
            'schoolInfo.campus.address:campus_id,region_code',
            'schoolInfo.course:id,course_id',
            'schoolInfo.course.course:id,name',
        ])->whereNotNull('award_year')->where('award_year', '<=', $asOfDate->year)->get();

        return Inertia::render('Web/dashboardPage', [
            'dashboardType' => $permissions->dashboardType($user),
            'asOf' => [
                'month' => $asOfMonth,
                'label' => $asOfDate->format('F Y'),
                'year' => (int) $asOfDate->year,
            ],
            ...$this->summarize($scholars, LocationRegions::select('code', 'region', 'name')->get()),
        ]);
    }

    public function summarize(Collection $scholars, Collection $regions): array
    {
        $categories = $scholars->pluck('award_year')->unique()->sort()->values();
        $programs = $scholars->groupBy(fn ($scholar) => $scholar->program?->name ?? 'Unspecified program');
        $latestSchools = $scholars->mapWithKeys(fn ($scholar) => [
            $scholar->id => $scholar->schoolInfo->sortByDesc('id')->first(),
        ]);
        $regionNames = $regions->mapWithKeys(fn ($region) => [
            $region->code => $region->region ?: $region->name,
        ]);
        $regionFor = function ($scholar) use ($latestSchools, $regionNames) {
            $code = $latestSchools->get($scholar->id)?->campus?->address?->region_code;

            return $code ? ($regionNames->get($code) ?: 'Region '.$code) : 'Unspecified region';
        };
        $sexFor = fn ($scholar) => match ($scholar->profile?->sex) {
            'F' => 'Female',
            'M' => 'Male',
            default => 'Unspecified / Other',
        };
        $regionGroups = $scholars->groupBy($regionFor)->sortKeys();
        $sexLabels = ['Female', 'Male', 'Unspecified / Other'];
        $schoolRows = $scholars->groupBy(fn ($scholar) => $latestSchools->get($scholar->id)?->campus?->id ?? 'unspecified')
            ->map(function ($rows, $campusId) use ($latestSchools, $regionFor, $scholars) {
                $campus = $latestSchools->get($rows->first()->id)?->campus;

                return [
                    'id' => $campusId,
                    'name' => $campus?->generated_name ?: ($campus ? 'Campus '.$campusId : 'Unspecified campus'),
                    'region' => $regionFor($rows->first()),
                    'total' => $rows->count(),
                    'percent' => $scholars->count() ? $rows->count() / $scholars->count() * 100 : 0,
                ];
            })->sortByDesc('total')->values();
        // Aggregate the same course across campuses by course ID, never by its display name.
        $courseRows = $scholars->groupBy(fn ($scholar) => $latestSchools->get($scholar->id)?->course?->course?->id ?? 'unspecified')
            ->map(function ($rows, $courseId) use ($latestSchools, $scholars) {
                $course = $latestSchools->get($rows->first()->id)?->course?->course;

                return [
                    'id' => $courseId,
                    'name' => Str::upper($course?->name ?: 'Unspecified course'),
                    'total' => $rows->count(),
                    'percent' => $scholars->count() ? $rows->count() / $scholars->count() * 100 : 0,
                ];
            })->sortByDesc('total')->values();
        $distribution = fn ($rows) => [
            'series' => [['data' => $rows->map(fn ($row) => [
                'id' => $row['id'], 'x' => $row['name'], 'y' => $row['total'],
            ])->values()]],
            'table' => $rows,
            'total' => $rows->sum('total'),
        ];
        $programTotals = $programs->map(fn ($rows, $name) => ['name' => $name, 'data' => $rows->count()])->values();

        return [
            'card' => [
                'total' => $scholars->count(),
                'activated' => $scholars->whereNotNull('activated_at')->count(),
                'undergraduate' => $scholars->where('type_id', 28)->count(),
                'jlss' => $scholars->where('type_id', 29)->count(),
                'otherTypes' => $scholars->whereNotIn('type_id', [28, 29])->count(),
                'graduated' => $scholars->filter(fn ($scholar) => Str::upper(trim($scholar->status?->name ?? '')) === 'GRADUATED')->count(),
            ],
            'timeline' => [
                'categories' => $categories,
                'series' => $programs->map(fn ($rows, $name) => [
                    'name' => $name,
                    'data' => $categories->map(fn ($year) => $rows->where('award_year', $year)->count())->all(),
                ])->values(),
                'programs' => $programTotals,
                'programSeries' => $programTotals->pluck('data')->all(),
            ],
            'school' => $distribution($schoolRows),
            'course' => $distribution($courseRows),
            'gender' => [
                'series' => collect($sexLabels)->map(fn ($label) => [
                    'name' => $label,
                    'data' => $regionGroups->map(fn ($rows, $region) => [
                        'x' => $region,
                        'y' => $rows->filter(fn ($scholar) => $sexFor($scholar) === $label)->count(),
                    ])->values(),
                ])->values(),
                'bar' => ['series' => [[
                    'name' => 'Scholars',
                    'data' => collect($sexLabels)->map(fn ($label) => $scholars->filter(fn ($scholar) => $sexFor($scholar) === $label)->count())->all(),
                ]]],
            ],
        ];
    }
}
