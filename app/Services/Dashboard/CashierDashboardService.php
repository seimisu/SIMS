<?php

namespace App\Services\Dashboard;

use App\Models\Batches;
use App\Services\Payroll\PayrollMonthlyCreditService;
use App\Support\SystemPermissions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CashierDashboardService
{
    public function render(Request $request, $user, SystemPermissions $permissions)
    {
        $credits = app(PayrollMonthlyCreditService::class);
        $approvedBatches = Batches::query()
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->where('is_historical', false)
                    ->orWhereNull('is_historical');
            })
            ->where('status', 'approved_payroll')
            ->with(['monthlyCredits.creditedBy.profile', 'latestLog'])
            ->withCount([
                'recipients as scholars_count' => fn ($query) => $query
                    ->where(function ($query) {
                        $query->where('is_for_removal_from_payroll', false)
                            ->orWhereNull('is_for_removal_from_payroll');
                    })
                    ->where('status', '!=', 'for_removal_from_payroll'),
            ])
            ->latest('updated_at')
            ->get();

        $creditRows = $approvedBatches->flatMap(fn ($batch) => $credits->rowsForBatch($batch));
        $creditedRows = $creditRows->where('status', 'credited');
        $pendingRows = $creditRows->where('status', 'pending');
        $recentBatches = $approvedBatches
            ->take(6)
            ->map(fn ($batch) => [
                'id' => $batch->id,
                'name' => $batch->name,
                'region' => $batch->region,
                'term' => $batch->academic_term,
                'school_year' => $batch->school_year,
                'scholars_count' => $batch->scholars_count,
                'credited' => $batch->monthlyCredits->where('status', 'credited')->count(),
                'total' => 5,
                'approved_at' => $batch->latestLog?->created_at
                    ? Carbon::parse($batch->latestLog->created_at)->format('M d, Y')
                    : null,
            ])
            ->values();

        $monthSummary = collect(range(1, 5))->map(fn ($month) => [
            'month_no' => $month,
            'label' => "Month {$month}",
            'credited' => $creditRows
                ->where('month_no', $month)
                ->where('status', 'credited')
                ->count(),
            'pending' => $creditRows
                ->where('month_no', $month)
                ->where('status', 'pending')
                ->count(),
        ]);

        return Inertia::render('Web/dashboardPage', [
            'dashboardType' => $permissions->dashboardType($user),
            'cashierDashboard' => [
                'summary' => [
                    'approved_batches' => $approvedBatches->count(),
                    'monthly_releases' => $creditRows->count(),
                    'credited_releases' => $creditedRows->count(),
                    'pending_releases' => $pendingRows->count(),
                ],
                'monthSummary' => $monthSummary,
                'recentBatches' => $recentBatches,
            ],
        ]);
    }
}
