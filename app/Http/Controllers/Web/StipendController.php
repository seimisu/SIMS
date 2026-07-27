<?php

namespace App\Http\Controllers\Web;

use App\Exports\PayrollExport;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Controller;
use App\Models\AllowanceType;
use App\Models\BatchRecipients;
use App\Models\Batches;
use App\Models\ListAgencies;
use App\Models\ListReferences;
use App\Models\ListStatuses;
use App\Models\LocationRegions;
use App\Models\PayrollBatchActivityLog;
use App\Models\PayrollBatchRevision;
use App\Models\PayrollBatchRevisionRecipient;
use App\Models\RecipientAllowance;
use App\Models\RecipientStipend;
use App\Models\RecipientWithheld;
use App\Models\Scholars;
use App\Models\ScholarTerm;
use App\Models\User;
use App\Support\SystemPermissions;
use Carbon\Carbon;
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

class StipendController extends Controller
{
    private function permissions(): SystemPermissions
    {
        return app(SystemPermissions::class);
    }

    private function payrollProcessStatus(string $batchStatus): string
    {
        return match ($batchStatus) {
            'rejected_payroll' => 'REJECTED',
            'submitted_payroll' => 'SUBMITTED',
            'approved_payroll' => 'APPROVED',
            default => 'DRAFT',
        };
    }

    private function currentBatchStatus(?Batches $batch): string
    {
        return $batch?->status ?: 'draft';
    }

    private function setBatchStatus(Batches $batch, string $status): void
    {
        $batch->forceFill(['status' => $status])->save();
    }

    private function scholarTermPayrollQuery(Batches $batch, int $scholarId)
    {
        return $this->scholarTermRowsQuery($batch, [$scholarId]);
    }

    private function scholarTermRowsQuery(Batches $batch, iterable $scholarIds)
    {
        $ids = collect($scholarIds)
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        $query = DB::table('scholar_term_records')
            ->where('academic_year', $batch->school_year);

        if ($ids->isEmpty()) {
            $query->whereRaw('1 = 0');
        } else {
            $query->whereIn('scholar_id', $ids);
        }

        if ($batch->term_id) {
            $query->where('term_id', $batch->term_id);
        } else {
            $query->whereExists(function ($query) use ($batch) {
                $query->select(DB::raw(1))
                    ->from('list_references')
                    ->whereColumn('list_references.id', 'scholar_term_records.term_id')
                    ->whereRaw('LOWER(list_references.name) = ?', [Str::lower($batch->academic_term)]);
            });
        }

        return $query;
    }

    private function scholarshipStatusesByScholar(Batches $batch, iterable $scholarIds)
    {
        $termRows = $this->scholarTermRowsQuery($batch, $scholarIds)
            ->select('id', 'scholar_id')
            ->get();

        if ($termRows->isEmpty()) {
            return collect();
        }

        $processStandings = DB::connection('scholars')
            ->table('scholar_processes')
            ->whereIn('term_record_id', $termRows->pluck('id'))
            ->pluck('scholarship_status', 'term_record_id');

        return $termRows
            ->mapWithKeys(fn($term) => [
                $term->scholar_id => $processStandings[$term->id] ?? null,
            ])
            ->filter();
    }

    private function syncScholarProcessPayrollStatus(iterable $terms, string $payrollStatus): void
    {
        foreach ($terms as $term) {
            DB::connection('scholars')
                ->table('scholar_processes')
                ->updateOrInsert(
                    ['term_record_id' => $term->id],
                    [
                        'spas_no' => $term->spas_no,
                        'payroll' => $payrollStatus,
                        'updated_at' => now(),
                        'updated_by' => $this->actorName(),
                    ]
                );
        }
    }

    private function updateScholarTermPayrollStatus(Batches $batch, int $scholarId, string $batchStatus): void
    {
        $terms = $this->scholarTermPayrollQuery($batch, $scholarId)
            ->join('scholars', 'scholars.id', '=', 'scholar_term_records.scholar_id')
            ->select('scholar_term_records.id', 'scholars.spas_no')
            ->get();

        if ($terms->isEmpty()) {
            return;
        }

        $this->syncScholarProcessPayrollStatus($terms, $this->payrollProcessStatus($batchStatus));
    }

    private function resetScholarTermToApproved(Batches $batch, int $scholarId): void
    {
        $terms = $this->scholarTermPayrollQuery($batch, $scholarId)
            ->join('scholars', 'scholars.id', '=', 'scholar_term_records.scholar_id')
            ->select('scholar_term_records.id', 'scholars.spas_no')
            ->get();

        if ($terms->isEmpty()) {
            return;
        }

        $this->syncScholarProcessPayrollStatus($terms, 'NOT SUBMITTED');
    }

    private function returnScholarTermFromPayroll(Batches $batch, int $scholarId): void
    {
        $terms = $this->scholarTermPayrollQuery($batch, $scholarId)
            ->join('scholars', 'scholars.id', '=', 'scholar_term_records.scholar_id')
            ->select('scholar_term_records.id', 'scholars.spas_no')
            ->get();

        if ($terms->isEmpty()) {
            return;
        }

        $this->syncScholarProcessPayrollStatus($terms, 'RETURNED');
    }

    private function syncBatchRecipientTermStatuses(Batches $batch, string $batchStatus): void
    {
        $batch->loadMissing('recipients:id,batch_id,scholar_id,status,is_for_removal_from_payroll');

        foreach ($batch->recipients as $recipient) {
            if ($recipient->is_for_removal_from_payroll || $recipient->status === 'for_removal_from_payroll') {
                continue;
            }

            if ($recipient->scholar_id) {
                $this->updateScholarTermPayrollStatus($batch, $recipient->scholar_id, $batchStatus);
            }
        }
    }

    private function payrollItemStatus(string $batchStatus): string
    {
        return match ($batchStatus) {
            'submitted_payroll' => 'submitted',
            'approved_payroll' => 'approved',
            'rejected_payroll' => 'rejected',
            default => 'pending',
        };
    }

    private function syncBatchFinancialStatuses(Batches $batch, string $batchStatus): void
    {
        $status = $this->payrollItemStatus($batchStatus);

        $batch->loadMissing('recipients.stipends', 'recipients.withhelds', 'recipients.allowances');

        foreach ($batch->recipients as $recipient) {
            if ($recipient->is_for_removal_from_payroll || $recipient->status === 'for_removal_from_payroll') {
                continue;
            }

            $recipient->update(['status' => $status]);
            $recipient->stipends()->update(['status' => $status]);
            $recipient->allowances()->where('amount', '>', 0)->update(['status' => $status]);
            $recipient->withhelds()->where('total_amount', '>', 0)->update(['status' => $status]);
        }
    }

    private function actorName(): ?string
    {
        return Auth::user()?->profile?->fullname;
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
        PayrollBatchActivityLog::create([
            'batch_id' => $batch->id,
            'batch_recipient_id' => $recipient?->id,
            'scholar_id' => $recipient?->scholar_id,
            'action' => $action,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'remarks' => $remarks,
            'metadata' => $metadata ?: null,
            'created_by' => $this->actorName(),
        ]);
    }

    private function latestSubmittedRevision(Batches $batch): ?PayrollBatchRevision
    {
        return PayrollBatchRevision::where('batch_id', $batch->id)
            ->orderByDesc('revision_no')
            ->first();
    }

    private function shouldShowSubmittedSnapshot(Batches $batch, string $status): bool
    {
        return $status === 'rejected_payroll'
            && $this->permissions()->isScholarshipReviewer(Auth::user())
            && ! $this->permissions()->isAdministrator(Auth::user());
    }

    private function createSubmittedRevision(Batches $batch, ?string $payrollFilePath, ?string $payrollFileName): void
    {
        $revisionNo = ((int) PayrollBatchRevision::where('batch_id', $batch->id)->max('revision_no')) + 1;
        $rows = $this->livePayrollRecipients($batch)
            ->reject(fn($row) => (bool) ($row['is_for_removal'] ?? false))
            ->values()
            ->all();

        $revision = PayrollBatchRevision::create([
            'batch_id' => $batch->id,
            'revision_no' => $revisionNo,
            'recipients_snapshot' => $rows,
            'totals_snapshot' => $this->payrollTotalsSnapshot($rows),
            'payroll_file_path' => $payrollFilePath,
            'payroll_file_name' => $payrollFileName,
            'submitted_by' => $this->actorName(),
            'submitted_at' => now(),
        ]);

        $this->storeRevisionRecipientRows(
            $revision,
            collect($rows)
        );

        BatchRecipients::where('batch_id', $batch->id)
            ->whereNotNull('moved_from_batch_id')
            ->whereNull('moved_notice_cleared_at')
            ->update(['moved_notice_cleared_at' => now()]);
    }

    private function storeRevisionRecipientRows(PayrollBatchRevision $revision, $rows): void
    {
        collect($rows)->each(function ($row) use ($revision) {
            $batchRecipientId = Hashids::decode($row['id'] ?? '')[0] ?? null;
            $scholarId = Hashids::decode($row['scholar_id'] ?? '')[0] ?? null;
            $isForRemoval = (bool) ($row['is_for_removal'] ?? false);

            PayrollBatchRevisionRecipient::updateOrCreate(
                [
                    'payroll_batch_revision_id' => $revision->id,
                    'batch_recipient_id' => $batchRecipientId,
                ],
                [
                    'batch_id' => $revision->batch_id,
                    'scholar_id' => $scholarId,
                    'row_payload' => [
                        ...$row,
                        'is_for_removal' => $isForRemoval,
                    ],
                    'is_for_removal' => $isForRemoval,
                ]
            );
        });
    }

    private function ensureRevisionRecipientRows(Batches $batch, PayrollBatchRevision $revision): void
    {
        if ($revision->recipients()->exists()) {
            return;
        }

        $forRemovalScholarIds = PayrollBatchActivityLog::where('batch_id', $batch->id)
            ->where('action', 'scholar_marked_for_removal')
            ->whereNotNull('scholar_id')
            ->pluck('scholar_id')
            ->map(fn($id) => (int) $id)
            ->all();

        $rows = collect($revision->recipients_snapshot ?? [])
            ->map(function ($row) use ($forRemovalScholarIds) {
                $rowScholarId = Hashids::decode($row['scholar_id'] ?? '')[0] ?? null;

                if (in_array((int) $rowScholarId, $forRemovalScholarIds, true)) {
                    $row['is_for_removal'] = true;
                }

                return $row;
            });

        $this->storeRevisionRecipientRows($revision, $rows);

        $revision->update([
            'recipients_snapshot' => $rows->values()->all(),
            'totals_snapshot' => $this->payrollTotalsSnapshot($rows->values()->all()),
        ]);
    }

    private function markRecipientForRemovalInLatestRevision(Batches $batch, BatchRecipients $recipient): void
    {
        $revision = $this->latestSubmittedRevision($batch);

        if (! $revision) {
            return;
        }

        $this->ensureRevisionRecipientRows($batch, $revision);

        $revision->recipients()
            ->where(function ($query) use ($recipient) {
                $query->where('batch_recipient_id', $recipient->id)
                    ->orWhere('scholar_id', $recipient->scholar_id);
            })
            ->get()
            ->each(function (PayrollBatchRevisionRecipient $snapshot) use ($recipient) {
                $row = $snapshot->row_payload ?? [];
                $row['is_for_removal'] = true;

                $snapshot->update([
                    'row_payload' => $row,
                    'is_for_removal' => true,
                    'marked_for_removal_by' => $recipient->marked_for_removal_by ?: $this->actorName(),
                    'marked_for_removal_at' => $recipient->marked_for_removal_at ?: now(),
                ]);
            });

        $rows = $this->submittedRevisionRecipients($batch, $revision)->values()->all();

        $revision->update([
            'recipients_snapshot' => $rows,
            'totals_snapshot' => $this->payrollTotalsSnapshot($rows),
        ]);
    }

    private function cancelRecipientForRemovalInLatestRevision(Batches $batch, BatchRecipients $recipient): void
    {
        $revision = $this->latestSubmittedRevision($batch);

        if (! $revision) {
            return;
        }

        $this->ensureRevisionRecipientRows($batch, $revision);

        $revision->recipients()
            ->where(function ($query) use ($recipient) {
                $query->where('batch_recipient_id', $recipient->id)
                    ->orWhere('scholar_id', $recipient->scholar_id);
            })
            ->get()
            ->each(function (PayrollBatchRevisionRecipient $snapshot) {
                $row = $snapshot->row_payload ?? [];
                $row['is_for_removal'] = false;

                $snapshot->update([
                    'row_payload' => $row,
                    'is_for_removal' => false,
                    'marked_for_removal_by' => null,
                    'marked_for_removal_at' => null,
                ]);
            });

        $rows = $this->submittedRevisionRecipients($batch, $revision)->values()->all();

        $revision->update([
            'recipients_snapshot' => $rows,
            'totals_snapshot' => $this->payrollTotalsSnapshot($rows),
        ]);
    }

    private function submittedRevisionRecipients(Batches $batch, PayrollBatchRevision $revision)
    {
        $this->ensureRevisionRecipientRows($batch, $revision);
        $forRemovalDetails = $this->forRemovalDetailsByScholar($batch);

        return $revision->recipients()
            ->orderBy('id')
            ->get()
            ->map(function (PayrollBatchRevisionRecipient $snapshot) use ($forRemovalDetails) {
                $details = $forRemovalDetails->get((int) $snapshot->scholar_id, []);

                return [
                    ...($snapshot->row_payload ?? []),
                    'is_for_removal' => (bool) $snapshot->is_for_removal,
                    'for_removal_reason' => $details['remarks'] ?? null,
                    'for_removal_by' => $details['created_by'] ?? $snapshot->marked_for_removal_by,
                    'for_removal_at' => $details['created_at'] ?? ($snapshot->marked_for_removal_at
                        ? Carbon::parse($snapshot->marked_for_removal_at)->format('M d, Y | h:i a')
                        : null),
                ];
            });
    }

    private function forRemovalDetailsByScholar(Batches $batch)
    {
        return PayrollBatchActivityLog::where('batch_id', $batch->id)
            ->where('action', 'scholar_marked_for_removal')
            ->whereNotNull('scholar_id')
            ->orderByDesc('created_at')
            ->get(['scholar_id', 'remarks', 'created_by', 'created_at'])
            ->unique('scholar_id')
            ->mapWithKeys(fn($log) => [
                (int) $log->scholar_id => [
                    'remarks' => $log->remarks,
                    'created_by' => $log->created_by,
                    'created_at' => $log->created_at
                        ? Carbon::parse($log->created_at)->format('M d, Y | h:i a')
                        : null,
                    'raw_created_at' => $log->created_at,
                ],
            ]);
    }

    private function movedFromReturnedPayrollDetailsByScholar(Batches $batch)
    {
        $latestSubmittedAt = PayrollBatchRevision::where('batch_id', $batch->id)
            ->max('submitted_at');

        return PayrollBatchActivityLog::where('batch_id', $batch->id)
            ->where('action', 'scholar_moved_from_returned_payroll')
            ->whereNotNull('scholar_id')
            ->when($latestSubmittedAt, fn($query) => $query->where('created_at', '>', $latestSubmittedAt))
            ->orderByDesc('created_at')
            ->get(['scholar_id', 'remarks', 'created_by', 'created_at', 'metadata'])
            ->unique('scholar_id')
            ->mapWithKeys(fn($log) => [
                (int) $log->scholar_id => [
                    'remarks' => $log->remarks,
                    'created_by' => $log->created_by,
                    'created_at' => $log->created_at
                        ? Carbon::parse($log->created_at)->format('M d, Y | h:i a')
                        : null,
                    'source_batch_name' => $log->metadata['source_batch_name'] ?? null,
                ],
            ]);
    }

    private function visiblePayrollActivityLogs(Batches $batch)
    {
        $user = Auth::user();

        $query = $batch->activityLogs()
            ->with('scholar.profile:scholar_id,fname,mname,lname,suffix')
            ->orderBy('created_at', 'desc');

        if ($this->permissions()->isAdministrator($user)) {
            return $query->limit(25)->get();
        }

        if ($this->permissions()->isRegionalRole($user)) {
            $query->whereIn('action', [
                'batch_created',
                'scholars_added',
                'scholar_moved_from_returned_payroll',
                'payroll_saved',
                'payroll_submitted',
                'payroll_returned',
                'scholar_removed',
            ]);
        } elseif ($this->permissions()->isScholarshipReviewer($user)) {
            $query->whereIn('action', [
                'payroll_submitted',
                'payroll_returned',
                'payroll_approved',
                'scholar_marked_for_removal',
                'scholar_moved_from_returned_payroll',
                'scholar_removal_cancelled',
            ]);
        }

        return $query->limit(25)->get();
    }

    private function payrollTotalsSnapshot(array $rows): array
    {
        return collect($rows)
            ->reject(fn($row) => (bool) ($row['is_for_removal'] ?? false))
            ->reduce(function ($totals, $row) {
                foreach (range(1, 5) as $month) {
                    $totals["month_{$month}"] += (float) ($row['months']["month_{$month}"] ?? 0);
                }

                $totals['total_withheld'] += (float) ($row['total_withheld'] ?? 0);
                $totals['learning_materials_amount'] += (float) ($row['learning_materials_amount'] ?? 0);
                $totals['clothing_amount'] += (float) ($row['clothing_amount'] ?? 0);
                $totals['grand_total'] += (float) ($row['grand_total'] ?? 0);

                return $totals;
            }, [
                'month_1' => 0,
                'month_2' => 0,
                'month_3' => 0,
                'month_4' => 0,
                'month_5' => 0,
                'total_withheld' => 0,
                'learning_materials_amount' => 0,
                'clothing_amount' => 0,
                'grand_total' => 0,
            ]);
    }

    private function allowanceTypeIds(): array
    {
        return AllowanceType::whereIn('code', [
            'connectivity',
            'clothing',
        ])
            ->pluck('id', 'code')
            ->all();
    }

    private function visibleFixedAllowanceDefaults(): array
    {
        return AllowanceType::whereIn('code', [
            'monthly_living',
            'connectivity',
            'clothing',
        ])
            ->where('is_variable', false)
            ->where('is_active', true)
            ->pluck('default_amount', 'code')
            ->map(fn($amount) => (float) $amount)
            ->all();
    }

    private function isPartialAllowanceStanding(?string $standing): bool
    {
        return Str::lower(trim((string) $standing)) === 'continue with partial allowance';
    }

    private function scholarStandingForBatch(Batches $batch, int $scholarId): ?string
    {
        $termIds = $this->scholarTermPayrollQuery($batch, $scholarId)->pluck('id');

        if ($termIds->isEmpty()) {
            return null;
        }

        return DB::connection('scholars')
            ->table('scholar_processes')
            ->whereIn('term_record_id', $termIds)
            ->value('scholarship_status');
    }

    private function recipientAllowanceAmount(BatchRecipients $recipient, string $code, string $legacyClassification, float $fallback): float
    {
        $allowance = $recipient->allowances->first(function ($allowance) use ($code, $legacyClassification) {
            return $allowance->allowanceType?->code === $code
                || $allowance->classification === $legacyClassification
                || $allowance->classification === $code;
        });

        return (float) ($allowance?->amount ?? $fallback);
    }


    private function payrollRegionCode(array|string|null $region): string
    {
        $regionCode = is_array($region) ? ($region['region_code'] ?? null) : null;

        if (! $regionCode) {
            throw new \RuntimeException('The selected agency does not have a region code.');
        }

        $locationRegion = LocationRegions::where('code', $regionCode)->first(['region']);

        if (! $locationRegion?->region) {
            throw new \RuntimeException('The selected agency region code does not match a location region.');
        }

        if (! preg_match('/^Region\s+([0-9ivxlcdm]+(?:-[ab])?)$/i', trim($locationRegion->region), $matches)) {
            throw new \RuntimeException('The matched location region does not use the expected Region format.');
        }

        return 'R' . $this->regionNumber($matches[1]);
    }

    private function selectedAgencyRegion(array|string|null $region): array
    {
        if (is_array($region) && !empty($region['region_code'])) {
            return ['region_code' => $region['region_code']];
        }

        $agency = null;

        if (is_array($region) && !empty($region['id'])) {
            $agency = ListAgencies::whereKey($region['id'])->first(['region_code']);
        }

        if (! $agency && is_array($region) && !empty($region['name'])) {
            $agency = ListAgencies::whereRaw('LOWER(name) = ?', [Str::lower($region['name'])])
                ->first(['region_code']);
        }

        if (! $agency && is_string($region)) {
            $agency = ListAgencies::whereRaw('LOWER(name) = ?', [Str::lower($region)])
                ->first(['region_code']);
        }

        return ['region_code' => $agency?->region_code];
    }

    private function regionNumber(string $value): string
    {
        $value = Str::upper(trim($value));
        $value = str_replace('-', '', $value);

        if (preg_match('/^(\d+)([A-Z]?)$/', $value, $matches)) {
            return $matches[1] . ($matches[2] ?? '');
        }

        if (preg_match('/^([IVXLCDM]+)([AB]?)$/', $value, $matches)) {
            $roman = $matches[1];
            $suffix = $matches[2] ?? '';
            $map = ['I' => 1, 'V' => 5, 'X' => 10, 'L' => 50, 'C' => 100, 'D' => 500, 'M' => 1000];
            $total = 0;
            $previous = 0;

            foreach (array_reverse(str_split($roman)) as $char) {
                $number = $map[$char] ?? 0;
                $total += $number < $previous ? -$number : $number;
                $previous = max($previous, $number);
            }

            return $total . $suffix;
        }

        return $value;
    }

    private function payrollTermCode(?string $term): string
    {
        $term = trim($term ?? '');

        if (preg_match('/\b(1st|2nd|3rd|4th)\b/i', $term, $matches)) {
            return Str::lower($matches[1]);
        }

        if (preg_match('/\b(first|second|third|fourth)\b/i', $term, $matches)) {
            return [
                'first' => '1st',
                'second' => '2nd',
                'third' => '3rd',
                'fourth' => '4th',
            ][Str::lower($matches[1])] ?? Str::of($term)->replace(' ', '')->toString();
        }

        return Str::of($term)->replaceMatches('/[^A-Za-z0-9]+/', '')->toString();
    }

    private function payrollBatchName(array|string|null $region, ?string $term, ?string $academicYear, string|int|null $batch): string
    {
        $region = $this->selectedAgencyRegion($region);

        $years = explode('-', $academicYear ?? '');
        $ay = count($years) === 2
            ? substr($years[0], -2) . substr($years[1], -2)
            : Str::of($academicYear ?? '')->replaceMatches('/[^0-9]+/', '')->substr(-4)->toString();
        $batchNo = Str::of((string) $batch)->replaceMatches('/^batch\s*/i', '')->replaceMatches('/[^A-Za-z0-9]+/', '')->toString();

        return $this->payrollRegionCode($region)
            . '_'
            . $this->payrollTermCode($term)
            . 'AY'
            . $ay
            . '_Batch'
            . $batchNo;
    }

    private function acceptingBatch(
        string $region,
        string $academicYear,
        int|string|null $termId,
        ?string $termName,
        ?int $excludeBatchId = null
    ): ?Batches
    {
        return Batches::query()
            ->whereNull('deleted_at')
            ->where('school_year', $academicYear)
            ->where('region', $region)
            ->where('status', 'draft')
            ->when($excludeBatchId, fn($query) => $query->where('id', '!=', $excludeBatchId))
            ->when($termId, fn($query) => $query->where('term_id', $termId), function ($query) use ($termName) {
                $query->whereRaw('LOWER(academic_term) = ?', [Str::lower($termName)]);
            })
            ->orderByDesc('created_at')
            ->first();
    }

    private function createAutoBatch(
        string $region,
        string $academicYear,
        int|string|null $termId,
        ?string $termName,
        string $remarks = 'Payroll batch was auto-created.'
    ): Batches
    {
        $batchNo = $this->calculatedNextBatchNumber($region, $academicYear, $termId, $termName);
        $batch = Batches::create([
            'region' => $region,
            'academic_term' => $termName,
            'term_id' => $termId,
            'school_year' => $academicYear,
            'name' => $this->payrollBatchName($region, $termName, $academicYear, $batchNo),
            'status' => 'draft',
        ]);

        $batch->logs()->create([
            'status' => 'draft',
            'action_by' => $region,
        ]);

        $this->logPayrollActivity($batch, 'batch_created', remarks: $remarks);

        return $batch;
    }

    private function moveMarkedRecipientsToNextBatch(Batches $sourceBatch): int
    {
        $recipients = BatchRecipients::with(['stipends', 'withhelds', 'allowances'])
            ->where('batch_id', $sourceBatch->id)
            ->where(function ($query) {
                $query->where('is_for_removal_from_payroll', true)
                    ->orWhere('status', 'for_removal_from_payroll');
            })
            ->get();

        if ($recipients->isEmpty()) {
            return 0;
        }

        $scholarIds = $recipients
            ->pluck('scholar_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($scholarIds->isEmpty()) {
            return 0;
        }

        $targetBatch = $this->acceptingBatch(
            $sourceBatch->region,
            $sourceBatch->school_year,
            $sourceBatch->term_id,
            $sourceBatch->academic_term,
            $sourceBatch->id
        ) ?? $this->createAutoBatch(
            $sourceBatch->region,
            $sourceBatch->school_year,
            $sourceBatch->term_id,
            $sourceBatch->academic_term,
            'Payroll batch was auto-created for scholars removed from a returned payroll.'
        );
        $removalDetails = $this->forRemovalDetailsByScholar($sourceBatch);

        foreach ($recipients as $recipient) {
            $details = $removalDetails->get((int) $recipient->scholar_id, []);

            $this->logPayrollActivity(
                $sourceBatch,
                'scholar_removed',
                $recipient,
                $recipient->status,
                null,
                $details['remarks'] ?? 'Scholar was removed from the returned payroll and moved to the next accepting payroll batch.',
                ['target_batch_id' => $targetBatch->id, 'target_batch_name' => $targetBatch->name]
            );

            $recipient->stipends()->delete();
            $recipient->withhelds()->delete();
            $recipient->allowances()->delete();
            $recipient->delete();
        }

        $result = $this->attachScholarsToBatch($targetBatch, $scholarIds, [$sourceBatch->id]);

        if ($result['attached'] > 0) {
            BatchRecipients::where('batch_id', $targetBatch->id)
                ->whereIn('scholar_id', $scholarIds)
                ->get()
                ->each(function (BatchRecipients $recipient) use ($targetBatch, $sourceBatch, $removalDetails) {
                    $details = $removalDetails->get((int) $recipient->scholar_id, []);
                    $recipient->update([
                        'moved_from_batch_id' => $sourceBatch->id,
                        'moved_from_batch_name' => $sourceBatch->name,
                        'moved_from_reason' => $details['remarks'] ?? null,
                        'moved_from_marked_by' => $details['created_by'] ?? null,
                        'moved_from_marked_at' => isset($details['raw_created_at'])
                            ? Carbon::parse($details['raw_created_at'])
                            : null,
                        'moved_notice_cleared_at' => null,
                    ]);

                    $this->logPayrollActivity(
                        $targetBatch,
                        'scholar_moved_from_returned_payroll',
                        $recipient,
                        null,
                        $recipient->status,
                        $details['remarks'] ?? "Scholar was moved from returned payroll {$sourceBatch->name}.",
                        [
                            'source_batch_id' => $sourceBatch->id,
                            'source_batch_name' => $sourceBatch->name,
                            'marked_for_removal_by' => $details['created_by'] ?? null,
                            'marked_for_removal_at' => $details['created_at'] ?? null,
                        ]
                    );
                });
        }

        return $result['attached'];
    }

    private function canScholarJoinPayroll(Scholars $scholar): bool
    {
        $eligibleStatusNames = ['NEW', 'ONGOING', 'GRADUATING'];
        $eligibleStatusIds = ListStatuses::where('type', 'progress')
            ->whereIn(DB::raw('UPPER(name)'), $eligibleStatusNames)
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();

        return in_array((int) $scholar->status_id, $eligibleStatusIds, true)
            || in_array(Str::upper(trim($scholar->academic_status ?? '')), $eligibleStatusNames, true);
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
                    'user'          => $q->latestLog?->action_by,
                    'created_at'    => $q->latestLog?->created_at
                        ? Carbon::parse($q->latestLog->created_at)->format('M d, Y | h:i a')
                        : null,
                    'remarks'       => $q->latestLog?->remarks,
                    'status'        => $this->currentBatchStatus($q),
                    'permissions'   => $permissions->payrollBatchPermissions($user, $q, $this->currentBatchStatus($q)),
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
        $years = collect();

        foreach (['scholar_term_records', 'term_records'] as $table) {
            if (! Schema::connection('pgsql')->hasTable($table)) {
                continue;
            }

            if (! Schema::connection('pgsql')->hasColumn($table, 'academic_year')
                || ! Schema::connection('pgsql')->hasColumn($table, 'verification_status')
            ) {
                continue;
            }

            $years = $years->merge(
                DB::connection('pgsql')
                    ->table($table)
                    ->whereRaw('LOWER(verification_status) = ?', ['approved'])
                    ->whereNotNull('academic_year')
                    ->pluck('academic_year')
            );
        }

        return $years
            ->filter(fn($year) => is_string($year) && preg_match('/^\d{4}-\d{4}$/', trim($year)))
            ->map(fn($year) => trim($year))
            ->unique()
            ->sortByDesc(fn($year) => (int) Str::before($year, '-'))
            ->take(5)
            ->values()
            ->map(fn($year) => [
                'id' => $year,
                'name' => $year,
            ]);
    }

    private function batchStatusOptions(): array
    {
        return [
            ['id' => 'draft', 'name' => 'Draft'],
            ['id' => 'submitted_payroll', 'name' => 'Submitted Payroll'],
            ['id' => 'rejected_payroll', 'name' => 'Returned Payroll'],
            ['id' => 'approved_payroll', 'name' => 'Approved Payroll'],
        ];
    }

    private function inputArrayValue(mixed $value, string $key = 'name'): mixed
    {
        if (is_array($value)) {
            return $value[$key] ?? $value['name'] ?? $value['id'] ?? null;
        }

        return $value;
    }

    private function calculatedNextBatchNumber(string $region, string $academicYear, int|string|null $termId, ?string $termName): int
    {
        $usedNumbers = Batches::query()
            ->whereNull('deleted_at')
            ->where('school_year', $academicYear)
            ->where('region', $region)
            ->when($termId, fn($query) => $query->where('term_id', $termId), function ($query) use ($termName) {
                $query->whereRaw('LOWER(academic_term) = ?', [Str::lower($termName)]);
            })
            ->pluck('name')
            ->map(function ($name) {
                preg_match('/(?:^|_)Batch(\d+)\b/i', (string) $name, $matches);

                return isset($matches[1]) ? (int) $matches[1] : null;
            })
            ->filter()
            ->unique()
            ->values();

        $next = 1;

        while ($usedNumbers->contains($next)) {
            $next++;
        }

        return $next;
    }

    private function allowanceMetadata(array $codes)
    {
        return AllowanceType::whereIn('code', $codes)
            ->where('is_active', true)
            ->get()
            ->sortBy(fn($allowance) => array_search($allowance->code, $codes, true))
            ->values()
            ->map(fn($allowance) => [
                'code' => $allowance->code,
                'name' => $allowance->name,
                'default_amount' => (float) ($allowance->default_amount ?? 0),
                'max_amount' => $allowance->max_amount !== null ? (float) $allowance->max_amount : null,
                'is_variable' => (bool) $allowance->is_variable,
            ]);
    }

    private function allowanceLimits()
    {
        return $this->allowanceMetadata(['connectivity', 'clothing'])
            ->keyBy('code');
    }

    private function payrollSignatoryOptions()
    {
        $user = Auth::user();
        $permissions = $this->permissions();

        return User::query()
            ->where('is_delete', false)
            ->where('is_active', true)
            ->whereHas('profile')
            ->with(['profile.agency'])
            ->when($permissions->shouldScopeToRegion($user), function ($query) use ($user) {
                $query->whereHas('profile', function ($profile) use ($user) {
                    $profile->where('agency_id', $user->profile?->agency_id);
                });
            })
            ->orderBy('email')
            ->get()
            ->map(fn($signatory) => [
                'id' => $signatory->id,
                'name' => $signatory->profile?->fullname ?? $signatory->email,
                'designation' => $signatory->profile?->designation,
                'agency' => $signatory->profile?->agency?->name,
            ])
            ->values();
    }

    private function payrollSignatory(int|string|null $id): ?array
    {
        if (! $id) {
            return null;
        }

        return $this->payrollSignatoryOptions()
            ->firstWhere('id', (int) $id);
    }

    private function payrollSignatories(array $ids): array
    {
        $allowedSignatories = $this->payrollSignatoryOptions()->keyBy('id');

        return collect($ids)
            ->map(fn($id) => $allowedSignatories->get((int) $id))
            ->filter()
            ->values()
            ->all();
    }

    private function enforceAllowanceMaximums(array $recipients): void
    {
        $maxAmounts = AllowanceType::whereIn('code', ['connectivity', 'clothing'])
            ->whereNotNull('max_amount')
            ->pluck('max_amount', 'code')
            ->map(fn($amount) => (float) $amount);

        if ($maxAmounts->isEmpty()) {
            return;
        }

        $fieldCodes = [
            'learning_materials_amount' => 'connectivity',
            'clothing_amount' => 'clothing',
        ];
        $errors = [];

        foreach ($recipients as $index => $recipient) {
            foreach ($fieldCodes as $field => $code) {
                if (! $maxAmounts->has($code)) {
                    continue;
                }

                $amount = (float) ($recipient[$field] ?? 0);
                $maxAmount = $maxAmounts[$code];

                if ($amount > $maxAmount) {
                    $errors["recipients.{$index}.{$field}"] = "The amount must not exceed {$maxAmount}.";
                }
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
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

        $batch = Batches::select('id', 'name', 'region', 'academic_term', 'term_id', 'school_year', 'status')
            ->whereId($batchId)
            ->first();

        if (!$batch) {
            return null;
        }

        $latestLog = $batch->logs()
            ->select($logColumns)
            ->latest('created_at')
            ->first();
        $status = $this->currentBatchStatus($batch);
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
        return match ($action) {
            'batch_created' => 'Batch created',
            'scholars_added' => 'Scholars added',
            'payroll_saved' => 'Payroll saved',
            'payroll_submitted' => 'Payroll submitted',
            'payroll_approved' => 'Payroll approved',
            'payroll_returned' => 'Payroll returned',
            'scholar_marked_for_removal' => 'Scholar marked for removal',
            'scholar_moved_from_returned_payroll' => 'Scholar moved from returned payroll',
            'scholar_removal_cancelled' => 'Scholar removal cancelled',
            'scholar_removed' => 'Scholar removed from draft payroll',
            default => Str::of($action)->replace('_', ' ')->headline()->toString(),
        };
    }

    private function payrollRecipients(string $hashId)
    {
        $batchId = Hashids::decode($hashId)[0] ?? 0;
        $batch = Batches::find($batchId);

        if (! $batch) {
            return collect();
        }

        $latestStatus = $this->currentBatchStatus($batch);
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
        $recipients = BatchRecipients::with([
            'scholar.profile:scholar_id,fname,mname,lname,suffix,email,birthdate',
            'scholar.program:id,name',
            'scholar.schoolInfo' => fn($q) => $q
                ->select('id', 'scholar_id', 'campus_id')
                ->latest('id')
                ->with('campus:id,name,generated_name,agency_id'),
            'stipends' => fn($q) => $q
                ->select('id', 'recipient_id', 'month_no', 'amount')
                ->orderBy('month_no'),
            'withhelds' => fn($q) => $q
                ->select('id', 'recipient_id', 'total_amount', 'remarks', 'status'),
            'allowances' => fn($q) => $q
                ->select('id', 'recipient_id', 'allowance_type_id', 'classification', 'amount')
                ->with('allowanceType:id,code'),
        ])
            ->select([
                'id',
                'batch_id',
                'scholar_id',
                'account_no',
                'period',
                'total_withheld',
                'remarks',
                'learning_materials_amount',
                'clothing_amount',
                'grand_total',
                'status',
                'is_for_removal_from_payroll',
                'marked_for_removal_by',
                'marked_for_removal_at',
                'moved_from_batch_id',
                'moved_from_batch_name',
                'moved_from_reason',
                'moved_from_marked_by',
                'moved_from_marked_at',
                'moved_notice_cleared_at',
            ])
            ->where('batch_id', $batch->id)
            ->orderBy('id')
            ->get();

        $processStandingsByScholar = $this->scholarshipStatusesByScholar(
            $batch,
            $recipients->pluck('scholar_id')->filter()->unique()
        );

        $forRemovalDetails = $this->forRemovalDetailsByScholar($batch);
        $shouldShowMovedDetails = $this->permissions()->isRegionalRole(Auth::user());

        return $recipients
            ->map(function ($recipient) use ($processStandingsByScholar, $forRemovalDetails, $shouldShowMovedDetails) {
                $learningMaterials = $this->recipientAllowanceAmount(
                    $recipient,
                    'connectivity',
                    'connectivity',
                    (float) $recipient->learning_materials_amount
                );
                $clothing = $this->recipientAllowanceAmount(
                    $recipient,
                    'clothing',
                    'clothing',
                    (float) $recipient->clothing_amount
                );
                $removalDetails = $forRemovalDetails->get((int) $recipient->scholar_id, []);
                $isMovedNoticeActive = $shouldShowMovedDetails
                    && $recipient->moved_from_batch_id
                    && ! $recipient->moved_notice_cleared_at;

                return [
                    'id' => Hashids::encode($recipient->id),
                    'scholar_id' => Hashids::encode($recipient->scholar_id),
                    'spas_no' => $recipient->scholar?->spas_no,
                    'account_no' => $recipient->account_no,
                    'name' => trim(collect([
                        $recipient->scholar?->profile?->lname,
                        $recipient->scholar?->profile?->fname,
                        $recipient->scholar?->profile?->mname,
                        $recipient->scholar?->profile?->suffix,
                    ])->filter()->join(' ')),
                    'program' => $recipient->scholar?->program?->name,
                    'university' => $recipient->scholar?->schoolInfo->first()?->campus?->generated_name
                        ?? $recipient->scholar?->schoolInfo->first()?->campus?->name,
                    'scholarship_status' => $processStandingsByScholar->get($recipient->scholar_id),
                    'period' => $recipient->period,
                    'months' => collect(range(1, 5))->mapWithKeys(fn($month) => [
                        "month_{$month}" => (float) ($recipient->stipends->firstWhere('month_no', $month)?->amount ?? 0),
                    ]),
                    'total_withheld' => (float) $recipient->total_withheld,
                    'remarks' => $recipient->remarks,
                    'learning_materials_amount' => (float) $learningMaterials,
                    'clothing_amount' => (float) $clothing,
                    'grand_total' => (float) $recipient->grand_total,
                    'status' => $recipient->status,
                    'is_for_removal' => (bool) $recipient->is_for_removal_from_payroll
                        || $recipient->status === 'for_removal_from_payroll',
                    'for_removal_reason' => $removalDetails['remarks'] ?? null,
                    'for_removal_by' => $removalDetails['created_by'] ?? $recipient->marked_for_removal_by,
                    'for_removal_at' => $removalDetails['created_at'] ?? ($recipient->marked_for_removal_at
                        ? Carbon::parse($recipient->marked_for_removal_at)->format('M d, Y | h:i a')
                        : null),
                    'is_moved_from_returned_payroll' => (bool) $isMovedNoticeActive,
                    'moved_removal_reason' => $isMovedNoticeActive ? $recipient->moved_from_reason : null,
                    'moved_removal_by' => $isMovedNoticeActive ? $recipient->moved_from_marked_by : null,
                    'moved_removal_at' => $isMovedNoticeActive && $recipient->moved_from_marked_at
                        ? Carbon::parse($recipient->moved_from_marked_at)->format('M d, Y | h:i a')
                        : null,
                    'moved_from_batch' => $isMovedNoticeActive ? $recipient->moved_from_batch_name : null,
                ];
            });
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

        $latestStatus = $this->currentBatchStatus($batch);

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

        if ($request->query('format') === 'pdf') {
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

        return Excel::download(new PayrollExport($batch, $rows, $preparedBy, $notedBy, $certifiedBy), $filenameBase . '.xlsx');
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
        $latestStatus = $this->currentBatchStatus($batch);
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

        $payrollFilePath = null;
        $payrollFileName = null;

        if (($data['status'] ?? null) === 'submitted_payroll' && $request->hasFile('payroll_file')) {
            $payrollFile = $request->file('payroll_file');
            $payrollFilePath = $payrollFile->store('payroll-submissions', 'public');
            $payrollFileName = $payrollFile->getClientOriginalName();
        }

        $logData = [
            'status' => $data['status'],
            'remarks' => $data['remarks'] ?? null,
            'action_by' => $this->actorName(),
        ];

        if (
            Schema::hasColumn('batches_logs', 'payroll_file_path')
            && Schema::hasColumn('batches_logs', 'payroll_file_name')
        ) {
            $logData['payroll_file_path'] = $payrollFilePath;
            $logData['payroll_file_name'] = $payrollFileName;
        }

        $movedScholarCount = 0;

        DB::transaction(function () use ($batch, $data, $logData, $payrollFilePath, $payrollFileName, $latestStatus, &$movedScholarCount) {
            $this->setBatchStatus($batch, $data['status']);
            $batch->logs()->create($logData);

            if ($data['status'] === 'submitted_payroll') {
                $this->createSubmittedRevision($batch, $payrollFilePath, $payrollFileName);
            }

            $this->logPayrollActivity(
                $batch,
                match ($data['status']) {
                    'submitted_payroll' => 'payroll_submitted',
                    'approved_payroll' => 'payroll_approved',
                    'rejected_payroll' => 'payroll_returned',
                },
                oldStatus: $latestStatus,
                newStatus: $data['status'],
                remarks: $data['remarks'] ?? null,
                metadata: array_filter([
                    'payroll_file_name' => $payrollFileName,
                ])
            );

            $movedScholarCount = $data['status'] === 'rejected_payroll'
                ? $this->moveMarkedRecipientsToNextBatch($batch)
                : 0;

            $this->syncBatchRecipientTermStatuses($batch, $data['status']);
            $this->syncBatchFinancialStatuses($batch, $data['status']);
        });

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

    private function attachScholarsToBatch(Batches $batch, iterable $scholarIds, array $ignoredDuplicateBatchIds = []): array
    {
        $scholarIds = collect($scholarIds)
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();
        $ignoredDuplicateBatchIds = collect($ignoredDuplicateBatchIds)
            ->push($batch->id)
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $latestStatus = $this->currentBatchStatus($batch);
        $scholars = Scholars::with(['profile:scholar_id,birthdate', 'landbank:scholar_id,account_number'])
            ->whereIn('id', $scholarIds)
            ->get()
            ->keyBy('id');
        $sameTermRecipientScholarIds = BatchRecipients::whereIn('scholar_id', $scholarIds)
            ->whereNotIn('batch_id', $ignoredDuplicateBatchIds)
            ->whereHas('batch', function ($query) use ($batch) {
                $query->where('region', $batch->region)
                    ->where('school_year', $batch->school_year)
                    ->whereNull('deleted_at');

                $batch->term_id
                    ? $query->where('term_id', $batch->term_id)
                    : $query->where('academic_term', $batch->academic_term);

            })
            ->pluck('scholar_id')
            ->map(fn($id) => (int) $id)
            ->flip();
        $standingsByScholar = $this->scholarshipStatusesByScholar($batch, $scholarIds);
        $allowanceDefaults = $this->visibleFixedAllowanceDefaults();
        $allowanceTypeIds = $this->allowanceTypeIds();
        $monthlyLiving = $allowanceDefaults['monthly_living'] ?? 0;
        $addedCount = 0;
        $attachedCount = 0;

        foreach ($scholarIds as $scholarId) {
            $scholar = $scholars->get($scholarId);

            if (!$scholar || $sameTermRecipientScholarIds->has($scholar->id)) {
                continue;
            }

            $standing = $standingsByScholar->get($scholar->id);
            $recipientMonthlyLiving = $this->isPartialAllowanceStanding($standing) ? $monthlyLiving / 2 : $monthlyLiving;
            $totalMonthlyLiving = $recipientMonthlyLiving * 5;
            $recipient = BatchRecipients::firstOrCreate(
                ['batch_id' => $batch->id, 'scholar_id' => $scholar->id],
                [
                    'account_no' => $scholar->landbank?->account_number,
                    'birthday' => $scholar->profile?->birthdate,
                    'period' => trim($batch->academic_term . ' AY ' . $batch->school_year),
                    'scholarship_status' => $standing,
                    'total_stipend' => $totalMonthlyLiving,
                    'learning_materials_amount' => $allowanceDefaults['connectivity'] ?? 0,
                    'clothing_amount' => $allowanceDefaults['clothing'] ?? 0,
                    'grand_total' => $totalMonthlyLiving + ($allowanceDefaults['connectivity'] ?? 0) + ($allowanceDefaults['clothing'] ?? 0),
                    'status' => 'pending',
                ]
            );

            if ($recipient->wasRecentlyCreated) {
                $addedCount++;

                foreach (range(1, 5) as $month) {
                    $recipient->stipends()->create([
                        'month_no' => $month,
                        'month' => 'Month ' . $month,
                        'amount' => $recipientMonthlyLiving,
                        'status' => 'pending',
                    ]);
                }

                foreach (['connectivity', 'clothing'] as $code) {
                    $amount = $allowanceDefaults[$code] ?? 0;

                    if ($amount > 0) {
                        $recipient->allowances()->create([
                            'allowance_type_id' => $allowanceTypeIds[$code] ?? null,
                            'classification' => $code,
                            'amount' => $amount,
                            'status' => 'pending',
                        ]);
                    }
                }
            }

            $attachedCount++;
            $this->updateScholarTermPayrollStatus($batch, $scholar->id, $latestStatus);
        }

        return ['added' => $addedCount, 'attached' => $attachedCount];
    }

    public function savePayroll(Request $request, string $id): RedirectResponse
    {
        $batchId = Hashids::decode($id)[0] ?? 0;
        $batch = Batches::findOrFail($batchId);
        $latestStatus = $this->currentBatchStatus($batch);

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
            DB::beginTransaction();
            $allowanceTypeIds = $this->allowanceTypeIds();

            foreach ($data['recipients'] as $item) {
                $recipientId = Hashids::decode($item['id'])[0] ?? 0;
                $recipient = BatchRecipients::where('batch_id', $batchId)->findOrFail($recipientId);

                if ($recipient->is_for_removal_from_payroll || $recipient->status === 'for_removal_from_payroll') {
                    continue;
                }

                $totalStipend = 0;
                foreach (range(1, 5) as $month) {
                    $amount = (float) ($item["month_{$month}"] ?? 0);
                    $totalStipend += $amount;

                    RecipientStipend::updateOrCreate(
                        [
                            'recipient_id' => $recipient->id,
                            'month_no' => $month,
                        ],
                        [
                            'month' => 'Month ' . $month,
                            'amount' => $amount,
                            'status' => $amount > 0 ? 'pending' : 'withheld',
                        ]
                    );
                }

                $totalWithheld = (float) ($item['total_withheld'] ?? 0);
                $learningMaterials = (float) ($item['learning_materials_amount'] ?? 0);
                $clothing = (float) ($item['clothing_amount'] ?? 0);
                $grandTotal = $totalStipend + $totalWithheld + $learningMaterials + $clothing;

                $recipient->update([
                    'total_stipend' => $totalStipend,
                    'total_withheld' => $totalWithheld,
                    'learning_materials_amount' => $learningMaterials,
                    'clothing_amount' => $clothing,
                    'grand_total' => $grandTotal,
                    'remarks' => $item['remarks'] ?? null,
                ]);

                foreach ([
                    'connectivity' => [
                        'classification' => 'connectivity',
                        'amount' => $learningMaterials,
                    ],
                    'clothing' => [
                        'classification' => 'clothing',
                        'amount' => $clothing,
                    ],
                ] as $code => $allowanceData) {
                    $amount = $allowanceData['amount'];
                    $classification = $allowanceData['classification'];

                    if ($amount <= 0) {
                        RecipientAllowance::where('recipient_id', $recipient->id)
                            ->where(function ($query) use ($classification, $code, $allowanceTypeIds) {
                                $query->where('classification', $classification)
                                    ->orWhere('classification', $code);

                                if (!empty($allowanceTypeIds[$code])) {
                                    $query->orWhere('allowance_type_id', $allowanceTypeIds[$code]);
                                }
                            })
                            ->delete();

                        continue;
                    }

                    RecipientAllowance::updateOrCreate(
                        [
                            'recipient_id' => $recipient->id,
                            'classification' => $classification,
                        ],
                        [
                            'allowance_type_id' => $allowanceTypeIds[$code] ?? null,
                            'amount' => $amount,
                            'remarks' => $item['remarks'] ?? null,
                            'status' => 'pending',
                        ]
                    );
                }

                if ($totalWithheld > 0) {
                    RecipientWithheld::updateOrCreate(
                        ['recipient_id' => $recipient->id, 'month_no' => null],
                        [
                            'total_amount' => $totalWithheld,
                            'remarks' => $item['remarks'] ?? null,
                            'status' => 'pending',
                        ]
                    );
                } else {
                    RecipientWithheld::where('recipient_id', $recipient->id)
                        ->whereNull('month_no')
                        ->delete();
                }
            }

            $this->logPayrollActivity($batch, 'payroll_saved', remarks: 'Payroll information was saved.');

            DB::commit();

            return redirect()->back()->with('flash', [
                'status' => 'success',
                'title' => 'Payroll saved',
                'message' => 'Payroll information was saved.',
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

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
            DB::beginTransaction();

            $recipient = BatchRecipients::with('batch.logs')->findOrFail($recipientId);
            $batch = $recipient->batch;
            $latestStatus = $this->currentBatchStatus($batch);

            if (
                ! $batch
                || ! $this->permissions()->can(Auth::user(), 'payroll.recipients.manage-removal')
                || ! $this->permissions()->canReviewPayroll(Auth::user(), $latestStatus)
            ) {
                DB::rollBack();

                return redirect()->back()->with('flash', [
                    'status' => 'error',
                    'title' => 'Unauthorized',
                    'message' => 'You are not allowed to mark scholars for removal from this payroll batch.',
                ]);
            }

            if ($recipient->is_for_removal_from_payroll || $recipient->status === 'for_removal_from_payroll') {
                DB::rollBack();

                return redirect()->back()->with('flash', [
                    'status' => 'error',
                    'title' => 'Already marked',
                    'message' => 'This scholar is already marked for removal from the payroll.',
                ]);
            }

            $oldRecipientStatus = $recipient->status;
            $remarks = trim($data['remarks']);

            $recipient->update([
                'is_for_removal_from_payroll' => true,
                'marked_for_removal_by' => $this->actorName(),
                'marked_for_removal_at' => now(),
            ]);

            $this->markRecipientForRemovalInLatestRevision($batch, $recipient);

            if ($recipient->scholar_id) {
                $this->returnScholarTermFromPayroll($batch, $recipient->scholar_id);
            }

            $this->logPayrollActivity(
                $batch,
                'scholar_marked_for_removal',
                $recipient,
                $oldRecipientStatus,
                'for_removal_from_payroll',
                $remarks
            );

            DB::commit();

            return redirect()->back()->with('flash', [
                'status' => 'success',
                'title' => 'Scholar marked for removal',
                'message' => 'Remove this scholar from the payroll before resubmitting.',
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

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
            DB::beginTransaction();

            $recipient = BatchRecipients::with('batch.logs')->findOrFail($recipientId);
            $batch = $recipient->batch;
            $latestStatus = $this->currentBatchStatus($batch);

            if (
                ! $batch
                || ! $this->permissions()->can(Auth::user(), 'payroll.recipients.manage-removal')
                || ! $this->permissions()->canReviewPayroll(Auth::user(), $latestStatus)
            ) {
                DB::rollBack();

                return redirect()->back()->with('flash', [
                    'status' => 'error',
                    'title' => 'Unauthorized',
                    'message' => 'You are not allowed to cancel removal marks from this payroll batch.',
                ]);
            }

            if (! $recipient->is_for_removal_from_payroll && $recipient->status !== 'for_removal_from_payroll') {
                DB::rollBack();

                return redirect()->back()->with('flash', [
                    'status' => 'error',
                    'title' => 'Not marked',
                    'message' => 'This scholar is not marked for removal.',
                ]);
            }

            $oldRecipientStatus = $recipient->status;
            $restoredStatus = $this->payrollItemStatus($latestStatus);

            $recipient->update([
                'is_for_removal_from_payroll' => false,
                'marked_for_removal_by' => null,
                'marked_for_removal_at' => null,
                'status' => $restoredStatus,
            ]);

            $this->cancelRecipientForRemovalInLatestRevision($batch, $recipient);

            if ($recipient->scholar_id) {
                $this->updateScholarTermPayrollStatus($batch, $recipient->scholar_id, $latestStatus);
            }

            $this->logPayrollActivity(
                $batch,
                'scholar_removal_cancelled',
                $recipient,
                $oldRecipientStatus,
                $restoredStatus,
                'Scholar removal mark was cancelled.'
            );

            DB::commit();

            return redirect()->back()->with('flash', [
                'status' => 'success',
                'title' => 'Removal cancelled',
                'message' => 'The scholar is back in the payroll list.',
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

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
                DB::beginTransaction();

                $batch = Batches::with('recipients.stipends', 'recipients.withhelds', 'recipients.allowances')->findOrFail($batchId);
                $latestStatus = $this->currentBatchStatus($batch);

                if (! $this->permissions()->payrollBatchPermissions(Auth::user(), $batch, $latestStatus)['canDelete']) {
                    DB::rollBack();

                    return redirect()->back()->with('flash', [
                        'status' => 'error',
                        'title' => 'Unauthorized',
                        'message' => 'You are not allowed to delete this payroll batch.',
                    ]);
                }

                foreach ($batch->recipients as $recipient) {
                    if ($recipient->scholar_id) {
                        $this->resetScholarTermToApproved($batch, $recipient->scholar_id);
                    }

                    $recipient->stipends()->delete();
                    $recipient->withhelds()->delete();
                    $recipient->allowances()->delete();
                    $recipient->delete();
                }

                $batch->logs()->delete();
                $batch->delete();

                DB::commit();

                return redirect()->back()->with('flash', [
                    'status' => 'success',
                    'title' => 'Batch deleted',
                    'message' => 'The payroll batch was deleted.',
                ]);
            } catch (\Throwable $th) {
                DB::rollBack();

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
                DB::beginTransaction();

                $recipient = BatchRecipients::findOrFail($recipientId);
                $latestStatus = $this->currentBatchStatus($recipient->batch);

                if (! $recipient->batch || ! $this->permissions()->isAdministrator(Auth::user())) {
                    DB::rollBack();

                    return redirect()->back()->with('flash', [
                        'status' => 'error',
                        'title' => 'Unauthorized',
                        'message' => 'Scholars can only be removed through the scholarship review return workflow.',
                    ]);
                }
            $batch = $recipient->batch;
            if ($batch && $recipient->scholar_id) {
                $this->returnScholarTermFromPayroll($batch, $recipient->scholar_id);
            }

            $recipient->stipends()->delete();
            $recipient->withhelds()->delete();
            $recipient->allowances()->delete();
            $this->logPayrollActivity(
                $batch,
                'scholar_removed',
                $recipient,
                $recipient->status,
                null,
                'Scholar was removed from a draft/returned payroll.'
            );
            $recipient->delete();

            DB::commit();

            return redirect()->back()->with('flash', [
                'status' => 'success',
                'title' => 'Payroll updated',
                'message' => 'The scholar is no longer included in this payroll.',
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Something went wrong.',
                'message' => $th->getMessage(),
            ]);
        }
    }
}
