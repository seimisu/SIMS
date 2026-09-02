<?php

namespace App\Services\Dashboard;

use App\Models\Batches;
use App\Models\LocationRegions;
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
class RegionalDashboardService
{
    public function render(Request $request, $user, SystemPermissions $permissions)
    {
        $regionCode = $user->profile->agency->region_code;
        $currentYear = Carbon::now()->year;
        $asOfMonth = $request->input('as_of_month');
        if (! is_string($asOfMonth) || ! preg_match('/^\d{4}-\d{2}$/', $asOfMonth)) {
            $asOfMonth = now()->format('Y-m');
        }

        $asOfDate = Carbon::createFromFormat('Y-m', $asOfMonth)->endOfMonth();
        $asOfYear = (int) $asOfDate->year;
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
            $asOfScholars = $scholars
                ->whereNotNull('activated_at')
                ->whereNotNull('award_year')
                ->where('award_year', '<=', $asOfYear)
                ->values();
            $payrollRegion = $permissions->agencyNameFor($user) ?? '';
            $payrollBatches = Batches::with(['latestLog', 'term:id,name'])
                ->whereNull('deleted_at')
                ->where('region', $payrollRegion)
                ->withCount('recipients')
                ->latest('updated_at')
                ->get()
                ->filter(fn ($batch) => $batch->updated_at && Carbon::parse($batch->updated_at)->lessThanOrEqualTo($asOfDate))
                ->values();
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
            $regionalScholarIds = $scholars->pluck('id')->filter()->values();
            $pendingSubmissions = [
                'grades' => ScholarTerm::where('verification_status', 'submitted')
                    ->whereIn('scholar_id', $regionalScholarIds)
                    ->count(),
                'profile' => StudentProfileRequest::where('status', 'pending')
                    ->whereIn('scholar_id', $regionalScholarIds)
                    ->count(),
                'landbank' => studentLandbankRequest::where('status', 'pending')
                    ->whereIn('scholar_id', $regionalScholarIds)
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

            $timelineTotal = $asOfScholars
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
                    'Ucnt' => $asOfScholars->where('type_id', 28)->count(),
                    'UTotalcnt' => $scholars
                        ->where('type_id', 28)
                        ->where('award_year', $currentYear)
                        ->count(),
                    'JTotalcnt' => $scholars->where('type_id', 29)
                        ->where('award_year', $currentYear)
                        ->count(),

                    'Jcnt' => $asOfScholars->where('type_id', 29)->count(),
                    'totalyear' => $scholars
                        ->where('award_year', $currentYear)
                        ->count(),
                    'total' => $asOfScholars->count(),
                ],
                'asOf' => [
                    'month' => $asOfMonth,
                    'label' => $asOfDate->format('F Y'),
                    'year' => $asOfYear,
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
                    'series' => $asOfScholars
                        ->groupBy(fn ($s) => $s->profile?->sex)
                        ->map(fn ($rows) => $rows->count())
                        ->values()
                        ->toArray(),
                    'result' => $asOfScholars->groupBy(fn ($s) => $s->profile?->sex)->map(function ($rows, $gender) {
                        return ['sex' => $gender, 'total' => $rows->count()];
                    })->values()->toArray(),
                ],
            ]);
    }

}
