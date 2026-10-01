<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Batches;
use App\Services\Payroll\PayrollActivityService;
use App\Services\Payroll\PayrollMonthlyCreditService;
use App\Services\Payroll\PayrollStatusService;
use App\Services\Notifications\RoleBellNotificationService;
use App\Support\SystemPermissions;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Vinkla\Hashids\Facades\Hashids;

class CashierCreditController extends Controller
{
    public function recipients(string $id, int $month): JsonResponse
    {
        abort_unless($month >= 1 && $month <= 5, 404);
        abort_unless(app(SystemPermissions::class)->can(Auth::user(), 'payroll.credits.view'), 403);

        $batch = Batches::findOrFail(Hashids::decode($id)[0] ?? 0);
        abort_unless(app(PayrollStatusService::class)->currentBatchStatus($batch) === 'approved_payroll', 422);

        $service = app(PayrollMonthlyCreditService::class);
        abort_unless($service->isCreditEligible($batch), 422);

        $recipients = $service->recipientRowsForMonth($batch, $month);

        return response()->json([
            'batch' => [
                'id' => $id,
                'name' => $batch->name,
                'region' => $batch->region,
                'term' => $batch->academic_term,
                'school_year' => $batch->school_year,
            ],
            'month' => $month,
            'recipients' => $recipients,
            'summary' => [
                'total' => $recipients->count(),
                'credited' => $recipients->where('status', 'credited')->count(),
                'pending' => $recipients->where('status', 'pending')->count(),
                'total_amount' => $recipients->sum('amount'),
                'credited_amount' => $recipients->where('status', 'credited')->sum('amount'),
            ],
        ]);
    }

    public function index(Request $request): Response
    {
        $permissions = app(SystemPermissions::class);

        if (! $permissions->can(Auth::user(), 'payroll.credits.view')) {
            abort(403);
        }

        $search = strtolower((string) $request->input('search'));
        $region = $request->input('region');
        $term = $request->input('term');
        $schoolYear = $request->input('school_year');
        $creditStatus = $request->input('credit_status');
        $credits = app(PayrollMonthlyCreditService::class);
        $baseQuery = Batches::query()
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->where('is_historical', false)
                    ->orWhereNull('is_historical');
            })
            ->where('status', 'approved_payroll');

        return Inertia::render('Web/cashierCreditPage', [
            'filters' => fn () => [
                'search' => $search,
                'region' => $region,
                'term' => $term,
                'school_year' => $schoolYear,
                'credit_status' => $creditStatus,
            ],
            'filterOptions' => fn () => [
                'regions' => (clone $baseQuery)
                    ->select('region as name')
                    ->whereNotNull('region')
                    ->distinct()
                    ->orderBy('region')
                    ->get(),
                'terms' => (clone $baseQuery)
                    ->select('academic_term as name')
                    ->whereNotNull('academic_term')
                    ->distinct()
                    ->orderBy('academic_term')
                    ->get(),
                'schoolYears' => (clone $baseQuery)
                    ->select('school_year as name')
                    ->whereNotNull('school_year')
                    ->distinct()
                    ->orderByDesc('school_year')
                    ->get(),
                'creditStatuses' => [
                    ['id' => 'pending', 'name' => 'Has Pending Month'],
                    ['id' => 'credited', 'name' => 'Complete Deposit'],
                ],
            ],
            'batches' => fn () => (clone $baseQuery)
                ->when($search, function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                            ->orWhereRaw('LOWER(region) LIKE ?', ["%{$search}%"])
                            ->orWhereRaw('LOWER(academic_term) LIKE ?', ["%{$search}%"])
                            ->orWhereRaw('LOWER(school_year) LIKE ?', ["%{$search}%"]);
                    });
                })
                ->when($region, fn ($query) => $query->where('region', $region))
                ->when($term, fn ($query) => $query->where('academic_term', $term))
                ->when($schoolYear, fn ($query) => $query->where('school_year', $schoolYear))
                ->when($creditStatus === 'pending', function ($query) {
                    $query->where(function ($query) {
                        $query->whereDoesntHave('monthlyCredits')
                            ->orWhereHas('monthlyCredits', fn ($credits) => $credits->whereIn('status', ['pending', 'partial']));
                    });
                })
                ->when($creditStatus === 'credited', function ($query) {
                    $query->whereHas('monthlyCredits', fn ($credits) => $credits->where('status', 'credited'), '=', 5);
                })
                ->with('monthlyCredits.creditedBy.profile')
                ->withCount([
                    'recipients as scholars_count' => fn ($query) => $query
                        ->where(function ($query) {
                            $query->where('is_for_removal_from_payroll', false)
                                ->orWhereNull('is_for_removal_from_payroll');
                        })
                        ->where('status', '!=', 'for_removal_from_payroll'),
                ])
                ->orderByDesc('updated_at')
                ->orderByDesc('created_at')
                ->paginate(10)
                ->withQueryString()
                ->through(fn ($batch) => [
                    'id' => Hashids::encode($batch->id),
                    'name' => $batch->name,
                    'region' => $batch->region,
                    'term' => $batch->academic_term,
                    'school_year' => $batch->school_year,
                    'scholars_count' => $batch->scholars_count,
                    'approved_at' => $this->approvedAt($batch),
                    'credits' => $credits->rowsForBatch($batch),
                ]),
        ]);
    }

    public function update(Request $request, string $id, int $month): RedirectResponse
    {
        if ($month < 1 || $month > 5) {
            abort(404);
        }

        $permissions = app(SystemPermissions::class);

        if (! $permissions->can(Auth::user(), 'payroll.credits.update')) {
            abort(403);
        }

        $batchId = Hashids::decode($id)[0] ?? 0;
        $batch = Batches::findOrFail($batchId);
        $status = app(PayrollStatusService::class)->currentBatchStatus($batch);

        if ($status !== 'approved_payroll') {
            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Payroll not approved',
                'message' => 'Only approved payroll batches can be marked as deposit.',
            ]);
        }

        $data = $request->validate([
            'recipients' => ['required', 'array', 'min:1'],
            'recipients.*.id' => ['required', 'string'],
            'recipients.*.selected' => ['required', 'boolean'],
            'recipients.*.remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        foreach ($data['recipients'] as $index => $recipient) {
            if (! $recipient['selected'] && blank($recipient['remarks'] ?? null)) {
                return redirect()->back()->withErrors([
                    "recipients.{$index}.remarks" => 'Remarks are required for scholars not selected for deposit.',
                ]);
            }
        }

        $monthlyCredits = app(PayrollMonthlyCreditService::class);

        if (! $monthlyCredits->isCreditEligible($batch)) {
            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Payroll not available for deposit',
                'message' => 'Historical imported payroll batches cannot be marked as deposit by the cashier.',
            ]);
        }

        $monthlyCredits->ensureForBatch($batch);

        if ($batch->monthlyCredits()->where('month_no', $month)->where('status', 'credited')->exists()) {
            return redirect()->back()->with('flash', [
                'status' => 'info',
                'title' => 'Already deposit',
                'message' => "Month {$month} was already marked as deposit.",
            ]);
        }

        $credit = DB::transaction(function () use ($batch, $month, $data) {
            $credit = app(PayrollMonthlyCreditService::class)->creditSelected(
                $batch,
                $month,
                Auth::id(),
                $data['recipients']
            );

            $totalRecipients = app(PayrollMonthlyCreditService::class)
                ->recipientRowsForMonth($batch, $month)
                ->count();

            app(PayrollActivityService::class)->log(
                $batch,
                'payroll_month_credited',
                oldStatus: 'pending',
                newStatus: $credit->status,
                metadata: [
                    'month_no' => $month,
                    'amount' => (float) $credit->amount,
                    'recipient_count' => $credit->recipient_count,
                    'total_recipient_count' => $totalRecipients,
                ]
            );

            return $credit;
        });

        app(RoleBellNotificationService::class)->notifyRegionalPayrollResult(
            (string) $batch->region,
            "payroll_month_{$credit->month_no}_credited",
            'Payroll month deposit',
            "{$batch->name} Month {$credit->month_no}: {$credit->recipient_count} scholar(s) deposited.",
            '/stipends',
            'payroll_batch_monthly_credits',
            $credit->id
        );

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title' => $credit->status === 'credited' ? 'Month deposit complete' : 'Partial month deposit',
            'message' => "Month {$credit->month_no}: {$credit->recipient_count} scholar(s) deposited.",
        ]);
    }

    private function approvedAt(Batches $batch): ?string
    {
        $approvedAt = $batch->logs()
            ->where('status', 'approved_payroll')
            ->latest('created_at')
            ->value('created_at');

        return $approvedAt ? Carbon::parse($approvedAt)->format('M d, Y | h:i a') : null;
    }
}
