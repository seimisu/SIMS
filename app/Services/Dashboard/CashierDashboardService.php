<?php

namespace App\Services\Dashboard;

use App\Models\Batches;
use App\Services\Payroll\PayrollMonthlyCreditService;
use App\Support\SystemPermissions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $pendingRows = $creditRows->whereIn('status', ['pending', 'partial']);
        $allowanceRows = DB::table('recipient_allowances')
            ->join('batch_recipients', 'recipient_allowances.recipient_id', '=', 'batch_recipients.id')
            ->whereIn('batch_recipients.batch_id', $approvedBatches->modelKeys())
            ->where('recipient_allowances.amount', '>', 0)
            ->where(function ($query) {
                $query->where('batch_recipients.is_for_removal_from_payroll', false)
                    ->orWhereNull('batch_recipients.is_for_removal_from_payroll');
            })
            ->where('batch_recipients.status', '!=', 'for_removal_from_payroll')
            ->selectRaw("
                batch_recipients.batch_id,
                COUNT(*) as total_count,
                COALESCE(SUM(recipient_allowances.amount), 0) as total_amount,
                SUM(CASE WHEN recipient_allowances.deposit_status = 'credited' THEN 1 ELSE 0 END) as credited_count,
                COALESCE(SUM(CASE WHEN recipient_allowances.deposit_status = 'credited' THEN recipient_allowances.amount ELSE 0 END), 0) as credited_amount
            ")
            ->groupBy('batch_recipients.batch_id')
            ->get()
            ->keyBy('batch_id');
        $allowanceSummary = [
            'total_count' => (int) $allowanceRows->sum('total_count'),
            'total_amount' => (float) $allowanceRows->sum('total_amount'),
            'credited_count' => (int) $allowanceRows->sum('credited_count'),
            'credited_amount' => (float) $allowanceRows->sum('credited_amount'),
        ];
        $allowanceSummary['pending_count'] = $allowanceSummary['total_count'] - $allowanceSummary['credited_count'];
        $allowanceSummary['pending_amount'] = $allowanceSummary['total_amount'] - $allowanceSummary['credited_amount'];
        $allowanceSummary['status'] = $allowanceSummary['total_count'] === 0
            ? 'none'
            : ($allowanceSummary['credited_count'] === 0
                ? 'pending'
                : ($allowanceSummary['credited_count'] >= $allowanceSummary['total_count'] ? 'credited' : 'partial'));
        $recentBatches = $approvedBatches
            ->take(6)
            ->map(function ($batch) use ($allowanceRows) {
                $allowances = $allowanceRows->get($batch->id);
                $allowanceTotal = (int) ($allowances?->total_count ?? 0);
                $allowanceCredited = (int) ($allowances?->credited_count ?? 0);

                return [
                    'id' => $batch->id,
                    'name' => $batch->name,
                    'region' => $batch->region,
                    'term' => $batch->academic_term,
                    'school_year' => $batch->school_year,
                    'scholars_count' => $batch->scholars_count,
                    'credited' => $batch->monthlyCredits->where('status', 'credited')->count(),
                    'total' => 5,
                    'financial_allowance' => [
                        'credited' => $allowanceCredited,
                        'pending' => $allowanceTotal - $allowanceCredited,
                        'total' => $allowanceTotal,
                        'credited_amount' => (float) ($allowances?->credited_amount ?? 0),
                        'total_amount' => (float) ($allowances?->total_amount ?? 0),
                        'status' => $allowanceTotal === 0
                            ? 'none'
                            : ($allowanceCredited === 0
                                ? 'pending'
                                : ($allowanceCredited >= $allowanceTotal ? 'credited' : 'partial')),
                    ],
                    'approved_at' => $batch->latestLog?->created_at
                        ? Carbon::parse($batch->latestLog->created_at)->format('M d, Y')
                        : null,
                ];
            })
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
                ->whereIn('status', ['pending', 'partial'])
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
                    'financial_allowances' => $allowanceSummary['total_count'],
                    'credited_financial_allowances' => $allowanceSummary['credited_count'],
                    'pending_financial_allowances' => $allowanceSummary['pending_count'],
                    'financial_allowance_amount' => $allowanceSummary['total_amount'],
                    'credited_financial_allowance_amount' => $allowanceSummary['credited_amount'],
                    'pending_financial_allowance_amount' => $allowanceSummary['pending_amount'],
                    'financial_allowance_status' => $allowanceSummary['status'],
                ],
                'monthSummary' => $monthSummary,
                'recentBatches' => $recentBatches,
            ],
        ]);
    }
}
