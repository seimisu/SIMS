<?php

namespace App\Http\Controllers\Web;

use App\Exports\PayrollExport;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Controller;
use App\Models\BatchRecipients;
use App\Models\Batches;
use App\Models\ListAgencies;
use App\Models\ListReferences;
use App\Models\PayrollBatchRevision;
use App\Models\Scholars;
use App\Models\ScholarTerm;
use App\Services\Payroll\HistoricalPayrollImportService;
use App\Services\Payroll\PayrollActivityService;
use App\Services\Payroll\PayrollAllowanceService;
use App\Services\Payroll\PayrollBatchOptionsService;
use App\Services\Payroll\PayrollBatchNamingService;
use App\Services\Payroll\PayrollBatchService;
use App\Services\Payroll\PayrollDeletionService;
use App\Services\Payroll\PayrollNotificationService;
use App\Services\Payroll\PayrollRecipientAttachmentService;
use App\Services\Payroll\PayrollRecipientRemovalService;
use App\Services\Payroll\PayrollRecipientService;
use App\Services\Payroll\PayrollRevisionService;
use App\Services\Payroll\PayrollSaveService;
use App\Services\Payroll\PayrollSignatoryService;
use App\Services\Payroll\PayrollScholarEligibilityService;
use App\Services\Payroll\PayrollStatusTransitionService;
use App\Services\Payroll\PayrollStatusService;
use App\Support\SystemPermissions;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Vinkla\Hashids\Facades\Hashids;

class PayrollController extends Controller
{
    private const BATCH_SCHOLAR_LIMIT = 300;

    private function permissions(): SystemPermissions
    {
        return app(SystemPermissions::class);
    }

    private function payrollStatuses(): PayrollStatusService
    {
        return app(PayrollStatusService::class);
    }

    private function payrollActivities(): PayrollActivityService
    {
        return app(PayrollActivityService::class);
    }

    private function payrollNames(): PayrollBatchNamingService
    {
        return app(PayrollBatchNamingService::class);
    }

    private function payrollOptions(): PayrollBatchOptionsService
    {
        return app(PayrollBatchOptionsService::class);
    }

    private function payrollBatches(): PayrollBatchService
    {
        return app(PayrollBatchService::class);
    }

    private function payrollAllowances(): PayrollAllowanceService
    {
        return app(PayrollAllowanceService::class);
    }

    private function payrollSignatoryService(): PayrollSignatoryService
    {
        return app(PayrollSignatoryService::class);
    }

    private function payrollEligibility(): PayrollScholarEligibilityService
    {
        return app(PayrollScholarEligibilityService::class);
    }

    private function historicalPayrollImports(): HistoricalPayrollImportService
    {
        return app(HistoricalPayrollImportService::class);
    }

    private function payrollRevisions(): PayrollRevisionService
    {
        return app(PayrollRevisionService::class);
    }

    private function payrollNotifications(): PayrollNotificationService
    {
        return app(PayrollNotificationService::class);
    }

    private function payrollRecipientsService(): PayrollRecipientService
    {
        return app(PayrollRecipientService::class);
    }

    private function payrollRecipientAttachments(): PayrollRecipientAttachmentService
    {
        return app(PayrollRecipientAttachmentService::class);
    }

    private function payrollSaves(): PayrollSaveService
    {
        return app(PayrollSaveService::class);
    }

    private function payrollDeletions(): PayrollDeletionService
    {
        return app(PayrollDeletionService::class);
    }

    private function payrollRecipientRemovals(): PayrollRecipientRemovalService
    {
        return app(PayrollRecipientRemovalService::class);
    }

    private function payrollStatusTransitions(): PayrollStatusTransitionService
    {
        return app(PayrollStatusTransitionService::class);
    }

    private function logPayrollActivity(
        Batches $batch,
        string $action,
        ?BatchRecipients $recipient = null,
        ?string $oldStatus = null,
        ?string $newStatus = null,
        ?string $remarks = null,
        array $metadata = []
    ): void {
        $this->payrollActivities()->log($batch, $action, $recipient, $oldStatus, $newStatus, $remarks, $metadata);
    }

    private function actorName(): ?string
    {
        return Auth::user()?->profile?->fullname;
    }

    private function latestSubmittedRevision(Batches $batch): ?PayrollBatchRevision
    {
        return $this->payrollRevisions()->latestSubmitted($batch);
    }

    private function shouldShowSubmittedSnapshot(Batches $batch, string $status): bool
    {
        return $this->payrollRevisions()->shouldShowSubmittedSnapshot($status);
    }

    private function submittedRevisionRecipients(Batches $batch, PayrollBatchRevision $revision)
    {
        return $this->payrollRevisions()->submittedRecipients($batch, $revision);
    }

    private function visiblePayrollActivityLogs(Batches $batch)
    {
        return $this->payrollActivities()->visibleLogs($batch);
    }

    private function payrollBatchName(array|string|null $region, ?string $term, ?string $academicYear, string|int|null $batch): string
    {
        return $this->payrollNames()->batchName($region, $term, $academicYear, $batch);
    }

    private function acceptingBatch(
        string $region,
        string $academicYear,
        int|string|null $termId,
        ?string $termName,
        ?int $excludeBatchId = null
    ): ?Batches
    {
        return $this->payrollBatches()->acceptingBatch($region, $academicYear, $termId, $termName, self::BATCH_SCHOLAR_LIMIT, $excludeBatchId);
    }

    private function createAutoBatch(
        string $region,
        string $academicYear,
        int|string|null $termId,
        ?string $termName,
        string $remarks = 'Payroll batch was auto-created.'
    ): Batches
    {
        return $this->payrollBatches()->createAutoBatch($region, $academicYear, $termId, $termName, $remarks);
    }

    private function canScholarJoinPayroll(Scholars $scholar): bool
    {
        return $this->payrollEligibility()->canJoinPayroll($scholar);
    }

    public function autoAttachApprovedTerm(ScholarTerm $term): ?Batches
    {
        $term->loadMissing([
            'term:id,name',
            'scholar.status:id,name',
            'schoolInfo.campus.agency:id,name',
        ]);

        $scholar = $term->scholar;
        $region = $term->schoolInfo?->campus?->agency?->name;
        $termName = $term->term?->name;

        if (!$scholar || !$region || !$term->academic_year || (!$term->term_id && !$termName) || !$this->canScholarJoinPayroll($scholar)) {
            return null;
        }

        $batch = $this->acceptingBatch($region, $term->academic_year, $term->term_id, $termName)
            ?? $this->createAutoBatch(
                $region,
                $term->academic_year,
                $term->term_id,
                $termName,
                'Payroll batch was auto-created by grade validation.'
            );

        $result = $this->attachScholarsToBatch($batch, [$scholar->id]);

        if ($result['attached'] > 0) {
            $this->logPayrollActivity(
                $batch,
                'scholars_added',
                remarks: '1 scholar was auto-attached after grade validation.',
                metadata: ['source' => 'grade_validation', 'term_record_id' => $term->id]
            );
        }

        return $batch;
    }

    public function index(Request $request): Response
    {
        $user = Auth::user();
        $permissions = $this->permissions();
        $batchRegion = $this->inputArrayValue($request->input('region'));
        $batchTerm = $request->input('term_id');
        $batchTermName = $request->input('term_name');
        $batchAcademicYear = $this->inputArrayValue($request->input('academic_year'));
        $batchStatus = $this->inputArrayValue($request->input('status'));
        $search = Str::lower($request->input('search'));

        return Inertia::render('Web/stipendPage', [
            'payrollPermissions' => fn() => [
                'regionLocked' => $permissions->shouldScopeToRegion($user),
                'canImportHistorical' => $permissions->can($user, 'payroll.update'),
            ],
            'agencyOption' => fn() => ListAgencies::where('is_active', true)
                ->where('is_delete', false)
                ->when($permissions->shouldScopeToRegion($user), function ($query) use ($user) {
                    $query->whereKey($user->profile?->agency_id);
                })
                ->get()
                ->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'slug' => $role->slug,
                        'region_code' => $role->region_code,
                    ];
                }),
            'termOptions' => fn() => ListReferences::where('is_active', true)
                ->where('is_delete', false)
                ->whereRaw("TRIM(type) = 'Term'")
                ->orderBy('classification')
                ->orderBy('id')
                ->get()
                ->map(fn($term) => [
                    'id' => $term->id,
                    'name' => trim($term->classification . ' - ' . $term->name),
                    'term_name' => $term->name,
                ]),
            'academicYearOptions' => fn() => $this->academicYearOptions(),
            'statusOptions' => fn() => $this->batchStatusOptions(),
            'batchFilters' => fn() => [
                'region' => $batchRegion,
                'term_id' => $batchTerm,
                'term_name' => $batchTermName,
                'academic_year' => $batchAcademicYear,
                'status' => $batchStatus,
            ],
            'batches' => fn() => Batches::whereNull('deleted_at')
                ->when($permissions->shouldScopeToRegion($user), function ($query) use ($permissions, $user) {
                    $query->where('region', $permissions->agencyNameFor($user) ?? '');
                })
                ->when($batchRegion, function ($query) use ($batchRegion) {
                    $query->where('region', $batchRegion);
                })
                ->when($search, function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                            ->orWhereRaw('LOWER(region) LIKE ?', ["%{$search}%"]);
                    });
                })
                ->when($batchTerm || $batchTermName, function ($query) use ($batchTerm, $batchTermName) {
                    $query->where(function ($query) use ($batchTerm, $batchTermName) {
                        if (is_numeric($batchTerm)) {
                            $query->where('term_id', $batchTerm);
                        }

                        if ($batchTermName) {
                            $query->orWhereRaw('LOWER(academic_term) = ?', [Str::lower($batchTermName)]);
                        }
                    });
                })
                ->when($batchAcademicYear, function ($query) use ($batchAcademicYear) {
                    $query->where('school_year', $batchAcademicYear);
                })
                ->when($batchStatus, function ($query) use ($batchStatus) {
                    $query->where('status', $batchStatus);
                })
                ->when($permissions->isScholarshipReviewer($user), function ($query) {
                    $query->whereIn('status', ['submitted_payroll', 'rejected_payroll', 'approved_payroll']);
                })
                ->with([
                    'latestLog'
                ])
                ->withCount([
                    'recipients as scholars_count' => fn($query) => $query
                        ->where(function ($query) {
                            $query->where('is_for_removal_from_payroll', false)
                                ->orWhereNull('is_for_removal_from_payroll');
                        })
                        ->where('status', '!=', 'for_removal_from_payroll'),
                ])
                ->orderByDesc('updated_at')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate(10)
                ->withQueryString()
                ->through(fn($q) => [
                    'id'            => Hashids::encode($q->id),
                    'name'          => $q->name,
                    'region'        => $q->region,
                    'term'          => $q->academic_term,
                    'sy'            => $q->school_year,
                    'scholars_count' => $q->scholars_count,
                    'scholars_limit' => self::BATCH_SCHOLAR_LIMIT,
                    'user'          => $q->latestLog?->action_by,
                    'created_at'    => $q->latestLog?->created_at
                        ? Carbon::parse($q->latestLog->created_at)->format('M d, Y | h:i a')
                        : null,
                    'remarks'       => $q->latestLog?->remarks,
                    'status'        => $this->payrollStatuses()->currentBatchStatus($q),
                    'source'        => $q->source,
                    'is_historical' => (bool) $q->is_historical,
                    'permissions'   => $permissions->payrollBatchPermissions($user, $q, $this->payrollStatuses()->currentBatchStatus($q)),
                ]),
            'details' => fn() => request('id')
                ? $this->batchDetails(request('id'))
                : null,
            'payrollRecipients' => fn() => request('id')
                ? $this->payrollRecipients(request('id'))
                : null,
            'allowanceLimits' => fn() => request('id')
                ? $this->allowanceLimits()
                : [],
            'signatoryOptions' => fn() => request('id')
                ? $this->payrollSignatoryOptions()
                : [],
        ]);
    }

    private function academicYearOptions()
    {
        return $this->payrollOptions()->academicYearOptions();
    }

    private function batchStatusOptions(): array
    {
        return $this->payrollOptions()->statusOptions();
    }

    private function inputArrayValue(mixed $value, string $key = 'name'): mixed
    {
        return $this->payrollOptions()->inputArrayValue($value, $key);
    }

    private function allowanceLimits()
    {
        return $this->payrollAllowances()->limits();
    }

    private function payrollSignatoryOptions()
    {
        return $this->payrollSignatoryService()->options();
    }

    private function payrollSignatory(int|string|null $id): ?array
    {
        return $this->payrollSignatoryService()->find($id);
    }

    private function payrollSignatories(array $ids): array
    {
        return $this->payrollSignatoryService()->findMany($ids);
    }

    private function enforceAllowanceMaximums(array $recipients): void
    {
        $this->payrollAllowances()->enforceMaximums($recipients);
    }

    private function batchDetails(string $hashId): ?array
    {
        $batchId = Hashids::decode($hashId)[0] ?? 0;
        $hasPayrollFileColumns = Schema::hasColumn('batches_logs', 'payroll_file_path')
            && Schema::hasColumn('batches_logs', 'payroll_file_name');
        $logColumns = ['id', 'batch_id', 'status', 'remarks', 'action_by', 'created_at'];

        if ($hasPayrollFileColumns) {
            $logColumns = array_merge($logColumns, ['payroll_file_path', 'payroll_file_name']);
        }

        $batch = Batches::select(
            'id',
            'name',
            'region',
            'academic_term',
            'term_id',
            'school_year',
            'status',
            'generated_excel_path',
            'generated_excel_name',
            'generated_excel_at',
        )
            ->whereId($batchId)
            ->first();

        if (!$batch) {
            return null;
        }

        $latestLog = $batch->logs()
            ->select($logColumns)
            ->latest('created_at')
            ->first();
        $status = $this->payrollStatuses()->currentBatchStatus($batch);
        $showingSubmittedSnapshot = $this->shouldShowSubmittedSnapshot($batch, $status)
            && (bool) $this->latestSubmittedRevision($batch);
        $latestPayrollFile = $hasPayrollFileColumns
            ? $batch->logs()
                ->select($logColumns)
                ->whereNotNull('payroll_file_path')
                ->latest('created_at')
                ->first()
            : null;
        $permissions = $this->permissions()->payrollBatchPermissions(Auth::user(), $batch, $status);

        if (! $permissions['canView']) {
            return null;
        }

        $activityLogs = $this->visiblePayrollActivityLogs($batch);

        return [
            'id' => Hashids::encode($batch->id),
            'name' => $batch->name,
            'region' => $batch->region,
            'term' => $batch->academic_term,
            'term_id' => $batch->term_id,
            'school_year' => $batch->school_year,
            'status' => $status,
            'showing_submitted_snapshot' => $showingSubmittedSnapshot,
            'remarks' => $latestLog?->remarks,
            'remarks_by' => $latestLog?->action_by,
            'remarks_at' => $latestLog?->created_at
                ? Carbon::parse($latestLog->created_at)->format('M d, Y | h:i a')
                : null,
            'payroll_file' => $latestPayrollFile ? [
                'name' => $latestPayrollFile->payroll_file_name,
                'url' => Storage::disk('public')->url($latestPayrollFile->payroll_file_path),
                'uploaded_by' => $latestPayrollFile->action_by,
                'uploaded_at' => $latestPayrollFile->created_at
                    ? Carbon::parse($latestPayrollFile->created_at)->format('M d, Y | h:i a')
                    : null,
            ] : null,
            'generated_excel_file' => $batch->generated_excel_path ? [
                'name' => $batch->generated_excel_name ?: 'Generated payroll Excel',
                'url' => Storage::disk('public')->url($batch->generated_excel_path),
                'generated_at' => $batch->generated_excel_at
                    ? Carbon::parse($batch->generated_excel_at)->format('M d, Y | h:i a')
                    : null,
            ] : null,
            'activity_logs' => $activityLogs->map(fn($log) => [
                'id' => $log->id,
                'action' => $log->action,
                'label' => $this->payrollActivityLabel($log->action),
                'old_status' => $log->old_status,
                'new_status' => $log->new_status,
                'remarks' => $log->remarks,
                'created_by' => $log->created_by,
                'created_at' => $log->created_at
                    ? Carbon::parse($log->created_at)->format('M d, Y | h:i a')
                    : null,
                'scholar_name' => $log->scholar ? trim(collect([
                    $log->scholar?->profile?->lname,
                    $log->scholar?->profile?->fname,
                    $log->scholar?->profile?->mname,
                    $log->scholar?->profile?->suffix,
                ])->filter()->join(' ')) : null,
                'metadata' => $log->metadata,
            ]),
            'is_editable' => $permissions['canEdit'],
            'permissions' => $permissions,
        ];
    }

    private function payrollActivityLabel(string $action): string
    {
        return $this->payrollActivities()->label($action);
    }

    private function payrollRecipients(string $hashId)
    {
        $batchId = Hashids::decode($hashId)[0] ?? 0;
        $batch = Batches::find($batchId);

        if (! $batch) {
            return collect();
        }

        $latestStatus = $this->payrollStatuses()->currentBatchStatus($batch);
        $batchPermissions = $this->permissions()->payrollBatchPermissions(
            Auth::user(),
            $batch,
            $latestStatus
        );

        if (! $batchPermissions['canView']) {
            return collect();
        }

        if ($this->shouldShowSubmittedSnapshot($batch, $latestStatus)) {
            $revision = $this->latestSubmittedRevision($batch);

            if ($revision) {
                return $this->submittedRevisionRecipients($batch, $revision);
            }
        }

        return $this->livePayrollRecipients($batch);
    }

    private function livePayrollRecipients(Batches $batch)
    {
        return $this->payrollRecipientsService()->liveRows($batch);
    }

    private function payrollExportPayload(string $id): array
    {
        $batchId = Hashids::decode($id)[0] ?? 0;
        $batch = Batches::with([
            'logs' => fn($q) => $q
                ->select('id', 'batch_id', 'status', 'remarks', 'action_by', 'created_at')
                ->orderBy('created_at', 'desc'),
            'recipients.scholar.profile:scholar_id,fname,mname,lname,suffix,email,birthdate',
            'recipients.scholar.program:id,name',
            'recipients.scholar.schoolInfo' => fn($q) => $q
                ->select('id', 'scholar_id', 'campus_id')
                ->latest('id')
                ->with('campus:id,name,generated_name,agency_id'),
            'recipients.stipends' => fn($q) => $q->orderBy('month_no'),
            'recipients.withhelds' => fn($q) => $q->orderBy('month_no'),
            'recipients.allowances.allowanceType',
        ])->findOrFail($batchId);

        $latestStatus = $this->payrollStatuses()->currentBatchStatus($batch);

        if (! $this->permissions()->payrollBatchPermissions(Auth::user(), $batch, $latestStatus)['canView']) {
            abort(403);
        }

        $rows = $this->payrollRecipients($id)
            ->filter(fn($row) => ! ($row['is_for_removal'] ?? false))
            ->map(function ($row) {
                return [
                    ...$row,
                    'program' => $row['program'] ?: 'NO PROGRAM',
                    'university' => $row['university'] ?: '',
                    'month_1' => (float) ($row['months']['month_1'] ?? 0),
                    'month_2' => (float) ($row['months']['month_2'] ?? 0),
                    'month_3' => (float) ($row['months']['month_3'] ?? 0),
                    'month_4' => (float) ($row['months']['month_4'] ?? 0),
                    'month_5' => (float) ($row['months']['month_5'] ?? 0),
                ];
            })
            ->groupBy('program')
            ->sortKeys()
            ->all();

        preg_match('/Batch([A-Za-z0-9]+)/i', $batch->name ?? '', $batchMatches);
        $filenameBase = $this->payrollBatchName(
            $batch->region,
            $batch->academic_term,
            $batch->school_year,
            $batchMatches[1] ?? $batch->id
        );

        return [$batch, $rows, $filenameBase];
    }

    public function export(Request $request, string $id)
    {
        $data = $request->validate([
            'prepared_by' => ['required', 'array', 'min:1'],
            'prepared_by.*' => ['required', 'integer'],
            'noted_by' => ['required', 'integer'],
            'certified_by' => ['required', 'integer'],
        ]);

        $preparedBy = $this->payrollSignatories($data['prepared_by']);
        $notedBy = $this->payrollSignatory($data['noted_by']);
        $certifiedBy = $this->payrollSignatory($data['certified_by']);

        if (count($preparedBy) !== count(array_unique(array_map('intval', $data['prepared_by']))) || ! $notedBy || ! $certifiedBy) {
            abort(403);
        }

        [$batch, $rows, $filenameBase] = $this->payrollExportPayload($id);

        $latestStatus = $this->payrollStatuses()->currentBatchStatus($batch);
        $permissions = $this->permissions();

        if (
            ! $permissions->can($request->user(), 'payroll.export')
            || ! $permissions->canSubmitPayroll($request->user(), $batch, $latestStatus)
        ) {
            abort(403);
        }

        $previousExcelPath = $batch->generated_excel_path;
        $excelName = $filenameBase . '.xlsx';
        $excelPath = "payroll-generated/{$excelName}";
        Excel::store(new PayrollExport($batch, $rows, $preparedBy, $notedBy, $certifiedBy), $excelPath, 'public');

        if ($previousExcelPath && $previousExcelPath !== $excelPath) {
            Storage::disk('public')->delete($previousExcelPath);
        }

        $batch->forceFill([
            'generated_excel_path' => $excelPath,
            'generated_excel_name' => $excelName,
            'generated_excel_at' => now(),
        ])->save();

        return Pdf::loadView('exports.payroll_pdf', [
            'batch' => $batch,
            'rows' => $rows,
            'monthLabels' => collect(range(1, 5))->map(fn($month) => "Month {$month}"),
            'preparedBy' => $preparedBy,
            'notedBy' => $notedBy,
            'certifiedBy' => $certifiedBy,
        ])
            ->setPaper('legal', 'landscape')
            ->download($filenameBase . '.pdf');
    }

    public function importHistorical(Request $request): RedirectResponse
    {
        if (! $this->permissions()->can(Auth::user(), 'payroll.update')) {
            abort(403, 'Unauthorized');
        }

        [$file, $rows, $scholars] = $this->historicalPayrollImports()->validateImport($request);
        $storedPath = $file->store('payroll-historical-imports', 'public');

        [$createdBatches, $createdRecipients] = DB::transaction(function () use ($rows, $scholars, $storedPath, $file) {
            return $this->historicalPayrollImports()->store(
                $rows,
                $scholars,
                $storedPath,
                $file->getClientOriginalName(),
                $this->actorName()
            );
        });

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title' => 'Historical Payroll Imported',
            'message' => "{$createdRecipients} recipient(s) imported into {$createdBatches} historical payroll batch(es).",
        ]);
    }

    public function previewHistorical(Request $request)
    {
        if (! $this->permissions()->can(Auth::user(), 'payroll.update')) {
            abort(403, 'Unauthorized');
        }

        try {
            [$file, $rows, $scholars] = $this->historicalPayrollImports()->validateImport($request);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Exception $exception) {
            Log::error('Historical payroll preview failed.', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Unable to preview the uploaded payroll file.',
                'detail' => $exception->getMessage(),
            ], 422);
        }

        return response()->json($this->historicalPayrollImports()->previewPayload($file, $rows, $scholars));
    }

    public function update(Request $request, $id, $type)
    {
        if ($type !== 'status') {
            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Invalid action',
                'message' => 'The requested financial assistance action is not supported.',
            ]);
        }

        $batchId = Hashids::decode($id)[0] ?? 0;
        $data = $request->validate([
            'status' => ['required', 'in:submitted_payroll,rejected_payroll,approved_payroll'],
            'remarks' => ['required_if:status,rejected_payroll', 'nullable', 'string'],
            'payroll_file' => ['required_if:status,submitted_payroll', 'nullable', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:10240'],
        ]);

        $batch = Batches::findOrFail($batchId);
        $latestStatus = $this->payrollStatuses()->currentBatchStatus($batch);
        $permissions = $this->permissions();
        $isAllowed = match ($data['status']) {
            'submitted_payroll' => $permissions->canSubmitPayroll(Auth::user(), $batch, $latestStatus),
            'approved_payroll' => $permissions->can(Auth::user(), 'payroll.approve')
                && $permissions->canReviewPayroll(Auth::user(), $latestStatus),
            'rejected_payroll' => $permissions->can(Auth::user(), 'payroll.return')
                && $permissions->canReviewPayroll(Auth::user(), $latestStatus),
            default => false,
        };

        if (! $isAllowed) {
            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Unauthorized',
                'message' => 'You are not allowed to perform this payroll action.',
            ]);
        }

        if ($latestStatus === 'approved_payroll') {
            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Batch locked',
                'message' => 'Verified payroll batches can no longer be changed.',
            ]);
        }

        if (
            $data['status'] === 'approved_payroll'
            && BatchRecipients::where('batch_id', $batch->id)
                ->where(function ($query) {
                    $query->where('is_for_removal_from_payroll', true)
                        ->orWhere('status', 'for_removal_from_payroll');
                })
                ->exists()
        ) {
            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Payroll needs return',
                'message' => 'This payroll has scholar(s) marked for removal. Return the payroll before approving it.',
            ]);
        }

        $payrollFilePath = null;
        $payrollFileName = null;

        if (($data['status'] ?? null) === 'submitted_payroll' && $request->hasFile('payroll_file')) {
            $payrollFile = $request->file('payroll_file');
            $payrollFilePath = $payrollFile->store('payroll-submissions', 'public');
            $payrollFileName = $payrollFile->getClientOriginalName();
        }

        $movedScholarCount = DB::transaction(function () use ($batch, $data, $payrollFilePath, $payrollFileName, $latestStatus) {
            return $this->payrollStatusTransitions()->transition(
                $batch,
                $data['status'],
                $latestStatus,
                $data['remarks'] ?? null,
                $payrollFilePath,
                $payrollFileName,
                $this->actorName(),
                self::BATCH_SCHOLAR_LIMIT
            );
        });

        $this->sendPayrollBellNotification($batch, $data['status']);

        $successFlash = match ($data['status']) {
            'submitted_payroll' => [
                'title' => 'Payroll submitted',
                'message' => 'The payroll batch was successfully submitted.',
            ],
            'approved_payroll' => [
                'title' => 'Payroll approved',
                'message' => 'The payroll batch was successfully approved.',
            ],
            'rejected_payroll' => [
                'title' => 'Payroll rejected',
                'message' => $movedScholarCount > 0
                    ? "The payroll batch was returned. {$movedScholarCount} marked scholar(s) were moved to the next accepting payroll batch."
                    : 'The payroll batch was successfully rejected.',
            ],
        };

        return redirect()->back()->with('flash', [
            'status' => 'success',
            ...$successFlash,
        ]);
    }

    private function sendPayrollBellNotification(Batches $batch, string $status): void
    {
        $this->payrollNotifications()->sendStatusChange($batch, $status);
    }

    private function attachScholarsToBatch(Batches $batch, iterable $scholarIds, array $ignoredDuplicateBatchIds = []): array
    {
        return $this->payrollRecipientAttachments()->attachScholarsToBatch(
            $batch,
            $scholarIds,
            self::BATCH_SCHOLAR_LIMIT,
            $ignoredDuplicateBatchIds
        );
    }

    public function savePayroll(Request $request, string $id): RedirectResponse
    {
        $batchId = Hashids::decode($id)[0] ?? 0;
        $batch = Batches::findOrFail($batchId);
        $latestStatus = $this->payrollStatuses()->currentBatchStatus($batch);

        if (! $this->permissions()->canEditPayroll(Auth::user(), $batch, $latestStatus)) {
            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Unauthorized',
                'message' => 'You are not allowed to edit this payroll batch.',
            ]);
        }

        $data = $request->validate([
            'recipients' => ['required', 'array'],
            'recipients.*.id' => ['required', 'string'],
            'recipients.*.account_no' => ['nullable', 'string'],
            'recipients.*.scholarship_status' => ['nullable', 'string'],
            'recipients.*.period' => ['nullable', 'string'],
            'recipients.*.month_1' => ['nullable', 'numeric', 'min:0'],
            'recipients.*.month_2' => ['nullable', 'numeric', 'min:0'],
            'recipients.*.month_3' => ['nullable', 'numeric', 'min:0'],
            'recipients.*.month_4' => ['nullable', 'numeric', 'min:0'],
            'recipients.*.month_5' => ['nullable', 'numeric', 'min:0'],
            'recipients.*.total_withheld' => ['nullable', 'numeric', 'min:0'],
            'recipients.*.remarks' => ['nullable', 'string'],
            'recipients.*.learning_materials_amount' => ['nullable', 'numeric', 'min:0'],
            'recipients.*.clothing_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->enforceAllowanceMaximums($data['recipients']);

        try {
            DB::transaction(function () use ($batch, $batchId, $data) {
                $this->payrollSaves()->saveRecipients($batchId, $data['recipients']);
                $this->logPayrollActivity($batch, 'payroll_saved', remarks: 'Payroll information was saved.');
            });

            return redirect()->back()->with('flash', [
                'status' => 'success',
                'title' => 'Payroll saved',
                'message' => 'Payroll information was saved.',
            ]);
        } catch (\Throwable $th) {
            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Something went wrong.',
                'message' => $th->getMessage(),
            ]);
        }
    }

    public function markRecipientForRemoval(Request $request, string $id): RedirectResponse
    {
        $recipientId = Hashids::decode($id)[0] ?? 0;
        $data = $request->validate([
            'remarks' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $recipient = BatchRecipients::with('batch.logs')->findOrFail($recipientId);
            $batch = $recipient->batch;
            $latestStatus = $this->payrollStatuses()->currentBatchStatus($batch);

            if (
                ! $batch
                || ! $this->permissions()->can(Auth::user(), 'payroll.recipients.manage-removal')
                || ! $this->permissions()->canReviewPayroll(Auth::user(), $latestStatus)
            ) {
                return redirect()->back()->with('flash', [
                    'status' => 'error',
                    'title' => 'Unauthorized',
                    'message' => 'You are not allowed to mark scholars for removal from this payroll batch.',
                ]);
            }

            if ($recipient->is_for_removal_from_payroll || $recipient->status === 'for_removal_from_payroll') {
                return redirect()->back()->with('flash', [
                    'status' => 'error',
                    'title' => 'Already marked',
                    'message' => 'This scholar is already marked for removal from the payroll.',
                ]);
            }

            $remarks = trim($data['remarks']);
            DB::transaction(function () use ($batch, $recipient, $remarks) {
                $this->payrollRecipientRemovals()->markForRemoval($batch, $recipient, $remarks, $this->actorName());
            });

            return redirect()->back()->with('flash', [
                'status' => 'success',
                'title' => 'Scholar marked for removal',
                'message' => 'Remove this scholar from the payroll before resubmitting.',
            ]);
        } catch (\Throwable $th) {
            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Something went wrong.',
                'message' => $th->getMessage(),
            ]);
        }
    }

    public function cancelRecipientForRemoval(string $id): RedirectResponse
    {
        $recipientId = Hashids::decode($id)[0] ?? 0;

        try {
            $recipient = BatchRecipients::with('batch.logs')->findOrFail($recipientId);
            $batch = $recipient->batch;
            $latestStatus = $this->payrollStatuses()->currentBatchStatus($batch);

            if (
                ! $batch
                || ! $this->permissions()->can(Auth::user(), 'payroll.recipients.manage-removal')
                || ! $this->permissions()->canReviewPayroll(Auth::user(), $latestStatus)
            ) {
                return redirect()->back()->with('flash', [
                    'status' => 'error',
                    'title' => 'Unauthorized',
                    'message' => 'You are not allowed to cancel removal marks from this payroll batch.',
                ]);
            }

            if (! $recipient->is_for_removal_from_payroll && $recipient->status !== 'for_removal_from_payroll') {
                return redirect()->back()->with('flash', [
                    'status' => 'error',
                    'title' => 'Not marked',
                    'message' => 'This scholar is not marked for removal.',
                ]);
            }

            DB::transaction(function () use ($batch, $recipient, $latestStatus) {
                $this->payrollRecipientRemovals()->cancelRemoval($batch, $recipient, $latestStatus);
            });

            return redirect()->back()->with('flash', [
                'status' => 'success',
                'title' => 'Removal cancelled',
                'message' => 'The scholar is back in the payroll list.',
            ]);
        } catch (\Throwable $th) {
            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Something went wrong.',
                'message' => $th->getMessage(),
            ]);
        }
    }

    public function destroy($id, $type)
    {
        if ($type === 'batch') {
            $batchId = Hashids::decode($id)[0] ?? 0;

            try {
                $batch = Batches::with('recipients.stipends', 'recipients.withhelds', 'recipients.allowances')->findOrFail($batchId);
                $latestStatus = $this->payrollStatuses()->currentBatchStatus($batch);

                if (! $this->permissions()->payrollBatchPermissions(Auth::user(), $batch, $latestStatus)['canDelete']) {
                    return redirect()->back()->with('flash', [
                        'status' => 'error',
                        'title' => 'Unauthorized',
                        'message' => 'You are not allowed to delete this payroll batch.',
                    ]);
                }

                DB::transaction(function () use ($batch) {
                    $this->payrollDeletions()->deleteBatch($batch);
                });

                return redirect()->back()->with('flash', [
                    'status' => 'success',
                    'title' => 'Batch deleted',
                    'message' => 'The payroll batch was deleted.',
                ]);
            } catch (\Throwable $th) {
                return redirect()->back()->with('flash', [
                    'status' => 'error',
                    'title' => 'Something went wrong.',
                    'message' => $th->getMessage(),
                ]);
            }
        }

        if ($type !== 'recipient') {
            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Invalid action',
                'message' => 'The requested financial assistance action is not supported.',
            ]);
        }

        $recipientId = Hashids::decode($id)[0] ?? 0;

        try {
            $recipient = BatchRecipients::findOrFail($recipientId);

            if (! $recipient->batch || ! $this->permissions()->isAdministrator(Auth::user())) {
                return redirect()->back()->with('flash', [
                    'status' => 'error',
                    'title' => 'Unauthorized',
                    'message' => 'Scholars can only be removed through the scholarship review return workflow.',
                ]);
            }

            DB::transaction(function () use ($recipient) {
                $this->payrollDeletions()->deleteRecipient($recipient);
            });

            return redirect()->back()->with('flash', [
                'status' => 'success',
                'title' => 'Payroll updated',
                'message' => 'The scholar is no longer included in this payroll.',
            ]);
        } catch (\Throwable $th) {
            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Something went wrong.',
                'message' => $th->getMessage(),
            ]);
        }
    }
}

