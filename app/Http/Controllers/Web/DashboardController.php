<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Scholars;
use App\Models\SchoolCampuses;
use App\Support\SystemPermissions;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $permissions = app(SystemPermissions::class);

        $regionCode = $user->profile->agency->region_code;
        $currentYear = Carbon::now()->year;

        $scholars = Scholars::with([
            'program:id,name',
            'profile:sex,scholar_id',
        ])
            ->whereHas(
                'schoolInfo.campus.address',
                fn ($q) => $q->where('region_code', $regionCode)
            )
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
        $campuses = SchoolCampuses::select('id', 'generated_name', 'school_id', 'name')
            ->where([
                'agency_id' => Auth::user()->profile->agency_id,
                'is_delete' => false,
            ])
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
            ->get();

        // Duplicate for testing
        $campuses = $campuses->merge($campuses);
        if ($permissions->isRegionalStaff($user)) {
            return Inertia::render('Web/dashboardPage', [
                'dashboardType' => $permissions->dashboardType($user),
                'campus_cnt' => SchoolCampuses::when(! $permissions->isAdministrator($user), function ($q) {
                    $q->where('agency_id', Auth::user()->profile->agency_id);
                })->count(),
                'campuses_details' => ! $permissions->isAdministrator($user) && SchoolCampuses::where(['agency_id' => Auth::user()->profile->agency_id, 'is_delete' => false])->exists() ? SchoolCampuses::select('id', 'generated_name', 'school_id', 'name')->where(['agency_id' => Auth::user()->profile->agency_id, 'is_delete' => false])
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
            return Inertia::render('Web/dashboardPage', [
                'dashboardType' => $permissions->dashboardType($user),
                'schoolDetails' => SchoolCampuses::where(['id' => Auth::user()->school_id])
                    ->with(['address'])
                    ->first(),
            ]);
        } else {
            return Inertia::render('Web/dashboardPage', [
                'dashboardType' => $permissions->dashboardType($user),
                'result' => [],
            ]);
        }
    }
}
