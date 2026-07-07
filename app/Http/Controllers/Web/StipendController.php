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
use App\Models\LocationRegions;
use App\Models\RecipientAllowance;
use App\Models\RecipientStipend;
use App\Models\RecipientWithheld;
use App\Models\Scholars;
use App\Support\SystemPermissions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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

    private function scholarTermPayrollQuery(Batches $batch, int $scholarId)
    {
        $query = DB::table('scholar_term_records')
            ->where('scholar_id', $scholarId)
            ->where('academic_year', $batch->school_year);

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

        if ($batch->level_id) {
            $query->where('level_id', $batch->level_id);
        }

        return $query;
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
                        'updated_by' => Auth::user()->profile?->fullname,
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

    private function syncBatchRecipientTermStatuses(Batches $batch, string $batchStatus): void
    {
        $batch->loadMissing('recipients:id,batch_id,scholar_id');

        foreach ($batch->recipients as $recipient) {
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
            $recipient->update(['status' => $status]);
            $recipient->stipends()->update(['status' => $status]);
            $recipient->allowances()->where('amount', '>', 0)->update(['status' => $status]);
            $recipient->withhelds()->where('total_amount', '>', 0)->update(['status' => $status]);
        }
    }

    private function allowanceTypeIds(): array
    {
        return AllowanceType::whereIn('code', [
            'connectivity',
            'clothing',
            'tuition_school_fees',
            'transportation',
            'thesis',
            'graduation',
        ])
            ->pluck('id', 'code')
            ->all();
    }

    private function customAllowanceCodes(): array
    {
        return [
            'tuition_school_fees',
            'transportation',
            'thesis',
            'graduation',
        ];
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

    public function index(Request $request): Response
    {
        $user = Auth::user();
        $permissions = $this->permissions();

        return Inertia::render('Web/stipendPage', [
            'payrollPermissions' => [
                'canCreate' => $permissions->can($user, 'payroll.create'),
                'regionLocked' => $permissions->shouldScopeToRegion($user),
            ],
            'agencyOption' =>  ListAgencies::where('is_active', true)
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
            'termOptions' => ListReferences::where('is_active', true)
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
            'academicYearOptions' => $this->academicYearOptions(),
            'nextBatchNumber' => $this->nextBatchNumber($request),
            'batches' => Batches::whereNull('deleted_at')
                ->when($permissions->shouldScopeToRegion($user), function ($query) use ($permissions, $user) {
                    $query->whereRaw('LOWER(region) = ?', [Str::lower($permissions->agencyNameFor($user) ?? '')]);
                })
                ->when($permissions->isScholarshipReviewer($user), function ($query) {
                    $query->whereRaw("(
                        SELECT status
                        FROM batches_logs
                        WHERE batches_logs.batch_id = batches.id
                        ORDER BY created_at DESC
                        LIMIT 1
                    ) IN (?, ?, ?)", ['submitted_payroll', 'rejected_payroll', 'approved_payroll']);
                })
                ->with([
                    'level:id,name',
                    'logs' => fn($q) => $q
                        ->select('id', 'batch_id', 'status', 'remarks', 'action_by', 'created_at')
                        ->orderBy('created_at', 'desc')
                ])
                ->paginate(10)
                ->through(fn($q) => [
                    'id'            => Hashids::encode($q->id),
                    'name'          => $q->name,
                    'region'        => $q->region,
                    'term'          => $q->academic_term,
                    'level'         => $q->level?->name,
                    'sy'            => $q->school_year,
                    'user'          => $q->logs->first()?->action_by,
                    'created_at'    => $q->logs->first()?->created_at
                        ? Carbon::parse($q->logs->first()->created_at)->format('M d, Y | h:i a')
                        : null,
                    'remarks'       => $q->logs->first()?->remarks,
                    'status'        => $q->logs->first()?->status ?? 'draft',
                    'permissions'   => $permissions->payrollBatchPermissions($user, $q, $q->logs->first()?->status ?? 'draft'),
                ]),
            'details' => request('id')
                ? $this->batchDetails(request('id'))
                : null,
            'eligibleScholars' => request('id')
                ? $this->eligibleScholars(request('id'), $request)
                : null,
            'payrollRecipients' => request('id')
                ? $this->payrollRecipients(request('id'))
                : null,
            'allowanceOptions' => request('id')
                ? $this->allowanceOptions()
                : [],
            'allowanceLimits' => request('id')
                ? $this->allowanceLimits()
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
            ->whereRaw('LOWER(region) = ?', [Str::lower($region)])
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

    private function nextBatchNumber(Request $request): ?int
    {
        $region = $this->inputArrayValue($request->input('batch_region'));
        $academicYear = $this->inputArrayValue($request->input('batch_academic_year'));
        $term = $request->input('batch_term');
        $termId = is_array($term) ? ($term['id'] ?? null) : null;
        $termName = is_array($term) ? ($term['term_name'] ?? $term['name'] ?? null) : $term;

        if (! $region || ! $academicYear || (! $termId && ! $termName)) {
            return null;
        }

        return $this->calculatedNextBatchNumber($region, $academicYear, $termId, $termName);
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

    private function allowanceOptions()
    {
        return $this->allowanceMetadata($this->customAllowanceCodes());
    }

    private function allowanceLimits()
    {
        return $this->allowanceMetadata(['connectivity', 'clothing'])
            ->keyBy('code');
    }

    private function enforceAllowanceMaximums(array $recipients): void
    {
        $maxAmounts = AllowanceType::whereIn('code', array_merge(
            ['connectivity', 'clothing'],
            $this->customAllowanceCodes()
        ))
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

            foreach (($recipient['custom_allowances'] ?? []) as $code => $amount) {
                if (! $maxAmounts->has($code)) {
                    continue;
                }

                $amount = (float) ($amount ?? 0);
                $maxAmount = $maxAmounts[$code];

                if ($amount > $maxAmount) {
                    $errors["recipients.{$index}.custom_allowances.{$code}"] = "The amount must not exceed {$maxAmount}.";
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
        $batch = Batches::select('id', 'name', 'region', 'academic_term', 'term_id', 'level_id', 'school_year')
            ->with([
                'logs' => fn($q) => $q
                    ->select('id', 'batch_id', 'status', 'remarks', 'action_by', 'created_at')
                    ->orderBy('created_at', 'desc')
            ])
            ->whereId($batchId)
            ->first();

        if (!$batch) {
            return null;
        }

        $status = $batch->logs->first()?->status ?? 'draft';
        $permissions = $this->permissions()->payrollBatchPermissions(Auth::user(), $batch, $status);

        if (! $permissions['canView']) {
            return null;
        }

        return [
            'id' => Hashids::encode($batch->id),
            'name' => $batch->name,
            'region' => $batch->region,
            'term' => $batch->academic_term,
            'term_id' => $batch->term_id,
            'level_id' => $batch->level_id,
            'school_year' => $batch->school_year,
            'status' => $status,
            'remarks' => $batch->logs->first()?->remarks,
            'remarks_by' => $batch->logs->first()?->action_by,
            'remarks_at' => $batch->logs->first()?->created_at
                ? Carbon::parse($batch->logs->first()->created_at)->format('M d, Y | h:i a')
                : null,
            'is_editable' => $permissions['canEdit'],
            'permissions' => $permissions,
        ];
    }

    private function eligibleScholars(string $hashId, Request $request)
    {
        $batchId = Hashids::decode($hashId)[0] ?? 0;
        $batch = Batches::find($batchId);
        $search = Str::lower($request->input('eligible_search'));
        $program = Str::lower($request->input('eligible_program'));
        $university = Str::lower($request->input('eligible_university'));
        $status = Str::lower($request->input('eligible_status'));

        if (!$batch) {
            return Scholars::whereRaw('1 = 0')->paginate(10, ['*'], 'eligible_page');
        }

        $batchPermissions = $this->permissions()->payrollBatchPermissions(
            Auth::user(),
            $batch,
            $batch->logs()->latest('created_at')->value('status') ?? 'draft'
        );

        if (! $batchPermissions['canView']) {
            return Scholars::whereRaw('1 = 0')->paginate(10, ['*'], 'eligible_page');
        }

        $sameTermRecipientScholarIds = BatchRecipients::query()
            ->whereHas('batch', function ($query) use ($batch) {
                $query->where('region', $batch->region)
                    ->where('school_year', $batch->school_year)
                    ->whereNull('deleted_at');

                if ($batch->term_id) {
                    $query->where('term_id', $batch->term_id);
                } else {
                    $query->where('academic_term', $batch->academic_term);
                }

                if ($batch->level_id) {
                    $query->where('level_id', $batch->level_id);
                }
            })
            ->pluck('scholar_id')
            ->filter();

        $standingScholarIds = null;

        if ($status) {
            $standingTermRows = DB::table('scholar_term_records')
                ->where('academic_year', $batch->school_year)
                ->when($batch->term_id, function ($query) use ($batch) {
                    $query->where('term_id', $batch->term_id);
                }, function ($query) use ($batch) {
                    $query->whereExists(function ($exists) use ($batch) {
                        $exists->select(DB::raw(1))
                            ->from('list_references')
                            ->whereColumn('list_references.id', 'scholar_term_records.term_id')
                            ->whereRaw('LOWER(list_references.name) = ?', [Str::lower($batch->academic_term)]);
                    });
                })
                ->when($batch->level_id, fn($query) => $query->where('level_id', $batch->level_id))
                ->select('id', 'scholar_id')
                ->get();

            $standingTermIds = DB::connection('scholars')
                ->table('scholar_processes')
                ->whereIn('term_record_id', $standingTermRows->pluck('id'))
                ->whereRaw('LOWER(standing) = ?', [$status])
                ->pluck('term_record_id');

            $standingScholarIds = $standingTermRows
                ->whereIn('id', $standingTermIds)
                ->pluck('scholar_id')
                ->unique()
                ->values();
        }

        $eligibleScholars = Scholars::query()
            ->with([
                'profile:scholar_id,fname,mname,lname,suffix,email,birthdate',
                'program:id,name',
                'landbank:scholar_id,account_number',
                'schoolInfo' => fn($q) => $q
                    ->select('id', 'scholar_id', 'campus_id')
                    ->latest('id')
                    ->with('campus:id,name,generated_name,agency_id'),
            ])
            ->where('is_active', true)
            ->where('is_delete', false)
            ->whereHas('termRecords', function ($termQuery) use ($batch) {
                $termQuery->where('verification_status', 'approved')
                    ->where('academic_year', $batch->school_year);

                if ($batch->term_id) {
                    $termQuery->where('term_id', $batch->term_id);
                } else {
                    $termQuery->whereHas('term', function ($term) use ($batch) {
                        $term->whereRaw('LOWER(name) = ?', [Str::lower($batch->academic_term)]);
                    });
                }
            })
            ->when(!Str::contains(Str::lower($batch->region), 'science education institute'), function ($query) use ($batch) {
                $query->whereHas('schoolInfo.campus.agency', function ($agency) use ($batch) {
                    $agency->whereRaw('LOWER(name) = ?', [Str::lower($batch->region)]);
                });
            })
            ->whereNotIn('id', $sameTermRecipientScholarIds)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereRaw('LOWER(spas_no) LIKE ?', ["%{$search}%"])
                        ->orWhereHas('profile', function ($profile) use ($search) {
                            $profile->whereRaw("LOWER(CONCAT_WS(' ', fname, mname, lname, suffix)) LIKE ?", ["%{$search}%"]);
                        });
                });
            })
            ->when($program, function ($query) use ($program) {
                $query->whereHas('program', function ($programQuery) use ($program) {
                    $programQuery->whereRaw('LOWER(name) = ?', [$program]);
                });
            })
            ->when($university, function ($query) use ($university) {
                $query->whereHas('schoolInfo.campus', function ($campusQuery) use ($university) {
                    $campusQuery->whereRaw('LOWER(COALESCE(generated_name, name)) = ?', [$university]);
                });
            })
            ->when($status, function ($query) use ($standingScholarIds) {
                $standingScholarIds->isNotEmpty()
                    ? $query->whereIn('id', $standingScholarIds)
                    : $query->whereRaw('1 = 0');
            })
            ->paginate(10, ['*'], 'eligible_page');

        $pageScholarIds = $eligibleScholars->getCollection()->pluck('id')->filter()->unique();
        $standingsByScholar = collect();

        if ($pageScholarIds->isNotEmpty()) {
            $termRows = DB::table('scholar_term_records')
                ->whereIn('scholar_id', $pageScholarIds)
                ->where('academic_year', $batch->school_year)
                ->when($batch->term_id, function ($query) use ($batch) {
                    $query->where('term_id', $batch->term_id);
                }, function ($query) use ($batch) {
                    $query->whereExists(function ($exists) use ($batch) {
                        $exists->select(DB::raw(1))
                            ->from('list_references')
                            ->whereColumn('list_references.id', 'scholar_term_records.term_id')
                            ->whereRaw('LOWER(list_references.name) = ?', [Str::lower($batch->academic_term)]);
                    });
                })
                ->when($batch->level_id, fn($query) => $query->where('level_id', $batch->level_id))
                ->select('id', 'scholar_id')
                ->get();

            $processStandings = DB::connection('scholars')
                ->table('scholar_processes')
                ->whereIn('term_record_id', $termRows->pluck('id'))
                ->pluck('standing', 'term_record_id');

            $standingsByScholar = $termRows
                ->mapWithKeys(fn($term) => [
                    $term->scholar_id => $processStandings[$term->id] ?? null,
                ])
                ->filter();
        }

        return $eligibleScholars
            ->through(fn($scholar) => [
                'id' => Hashids::encode($scholar->id),
                'spas_no' => $scholar->spas_no,
                'name' => trim(collect([
                    $scholar->profile?->lname,
                    $scholar->profile?->fname,
                    $scholar->profile?->mname,
                    $scholar->profile?->suffix,
                ])->filter()->join(' ')),
                'email' => $scholar->profile?->email,
                'birthday' => $scholar->profile?->birthdate,
                'account_no' => $scholar->landbank?->account_number,
                'program' => $scholar->program?->name,
                'university' => $scholar->schoolInfo->first()?->campus?->generated_name
                    ?? $scholar->schoolInfo->first()?->campus?->name,
                'status' => $standingsByScholar->get($scholar->id),
            ]);
    }

    private function payrollRecipients(string $hashId)
    {
        $batchId = Hashids::decode($hashId)[0] ?? 0;
        $batch = Batches::find($batchId);

        if (! $batch) {
            return collect();
        }

        $batchPermissions = $this->permissions()->payrollBatchPermissions(
            Auth::user(),
            $batch,
            $batch->logs()->latest('created_at')->value('status') ?? 'draft'
        );

        if (! $batchPermissions['canView']) {
            return collect();
        }

        $recipients = BatchRecipients::with([
            'scholar.profile:scholar_id,fname,mname,lname,suffix,email,birthdate',
            'scholar.program:id,name',
            'scholar.schoolInfo' => fn($q) => $q
                ->select('id', 'scholar_id', 'campus_id')
                ->latest('id')
                ->with('campus:id,name,generated_name,agency_id'),
            'stipends' => fn($q) => $q->orderBy('month_no'),
            'withhelds' => fn($q) => $q->orderBy('month_no'),
            'allowances.allowanceType',
        ])
            ->where('batch_id', $batchId)
            ->orderBy('id')
            ->get();

        $processStandingsByScholar = collect();

        if ($batch && $recipients->isNotEmpty()) {
            $termQuery = DB::table('scholar_term_records')
                ->whereIn('scholar_id', $recipients->pluck('scholar_id')->filter()->unique())
                ->where('academic_year', $batch->school_year);

            if ($batch->term_id) {
                $termQuery->where('term_id', $batch->term_id);
            } else {
                $termQuery->whereExists(function ($query) use ($batch) {
                    $query->select(DB::raw(1))
                        ->from('list_references')
                        ->whereColumn('list_references.id', 'scholar_term_records.term_id')
                        ->whereRaw('LOWER(list_references.name) = ?', [Str::lower($batch->academic_term)]);
                });
            }

            if ($batch->level_id) {
                $termQuery->where('level_id', $batch->level_id);
            }

            $termRows = $termQuery
                ->select('id', 'scholar_id')
                ->get();

            $processStandings = DB::connection('scholars')
                ->table('scholar_processes')
                ->whereIn('term_record_id', $termRows->pluck('id'))
                ->pluck('standing', 'term_record_id');

            $processStandingsByScholar = $termRows
                ->mapWithKeys(fn($term) => [
                    $term->scholar_id => $processStandings[$term->id] ?? null,
                ])
                ->filter();
        }

        return $recipients
            ->map(function ($recipient) use ($processStandingsByScholar) {
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
                $customAllowances = $recipient->allowances
                    ->filter(fn($allowance) => in_array($allowance->allowanceType?->code ?? $allowance->classification, $this->customAllowanceCodes(), true))
                    ->mapWithKeys(fn($allowance) => [
                        ($allowance->allowanceType?->code ?? $allowance->classification) => (float) $allowance->amount,
                    ]);

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
                    'custom_allowances' => $customAllowances,
                    'grand_total' => (float) $recipient->grand_total,
                    'status' => $recipient->status,
                ];
            });
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $this->permissions()->can(Auth::user(), 'payroll.create')) {
            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Unauthorized',
                'message' => 'You are not allowed to create payroll batches.',
            ]);
        }

        try {
            DB::beginTransaction();
            $data = $request->validate([
                'region' => ['required', 'array'],
                'region.id' => ['required', 'integer', 'exists:list_agencies,id'],
                'region.name' => ['required', 'string'],
                'term' => ['required', 'array'],
                'term.id' => ['required', 'integer', 'exists:list_references,id'],
                'term.term_name' => ['nullable', 'string'],
                'term.name' => ['required', 'string'],
                'academic_year' => ['required', 'regex:/^\d{4}-\d{4}$/'],
                'batch' => ['required', 'string'],
            ]);

            if ($this->permissions()->shouldScopeToRegion(Auth::user())
                && (int) $data['region']['id'] !== (int) Auth::user()->profile?->agency_id
            ) {
                DB::rollBack();

                return redirect()->back()->with('flash', [
                    'status' => 'error',
                    'title' => 'Invalid region',
                    'message' => 'Regional users can only create payroll batches for their assigned region.',
                ]);
            }

            foreach ($data as $key => $value) {
                if (is_string($value)) {
                    $data[$key] = Str::lower(Str::trim($value));
                }
            }

            $termName = $data['term']['term_name'] ?? $data['term']['name'];
            $data['batch'] = (string) $this->calculatedNextBatchNumber(
                $data['region']['name'],
                $data['academic_year'],
                $data['term']['id'],
                $termName
            );
            $name = $this->payrollBatchName($data['region'], $termName, $data['academic_year'], $data['batch']);

            $exists = Batches::where('name', $name)
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                DB::rollBack();

                return redirect()->back()->with('flash', [
                    'status' => 'error',
                    'title'  => 'Batch already exists',
                    'message' => 'A payroll batch with the same region, term, academic year, and batch number already exists.',
                ]);
            }

            $parent = Batches::create([
                'region'        => $data['region']['name'],
                'academic_term' => $termName,
                'term_id'       => $data['term']['id'],
                'school_year'   => $data['academic_year'],
                'name'          => $name
            ]);

            $parent->logs()->create([
                'action_by' => Auth::user()->profile?->fullname
            ]);


            DB::commit();
            return redirect()->back()->with('flash', [
                'status' => 'success',
                'title'  => 'Payroll Batch created',
                'message' => 'Payroll batch successfully created.',
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title'  => 'Something went wrong.',
                'message' => $th->getMessage(),
            ]);
        }
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

        $latestStatus = $batch->logs->first()?->status ?? 'draft';

        if (! $this->permissions()->payrollBatchPermissions(Auth::user(), $batch, $latestStatus)['canView']) {
            abort(403);
        }

        $rows = $this->payrollRecipients($id)
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
                    'custom_allowances' => collect($row['custom_allowances'] ?? [])->all(),
                ];
            })
            ->groupBy('program')
            ->sortKeys()
            ->all();

        $customAllowanceCodes = collect($rows)
            ->flatten(1)
            ->flatMap(fn($row) => array_keys($row['custom_allowances'] ?? []))
            ->unique()
            ->values();

        $customAllowances = $this->allowanceOptions()
            ->filter(fn($allowance) => $customAllowanceCodes->contains($allowance['code']))
            ->values();

        $fallbackAllowances = $customAllowanceCodes
            ->diff($customAllowances->pluck('code'))
            ->map(fn($code) => [
                'code' => $code,
                'name' => Str::headline(str_replace('_', ' ', $code)),
            ]);

        $customAllowances = $customAllowances
            ->concat($fallbackAllowances)
            ->values()
            ->all();

        preg_match('/Batch([A-Za-z0-9]+)/i', $batch->name ?? '', $batchMatches);
        $filenameBase = $this->payrollBatchName(
            $batch->region,
            $batch->academic_term,
            $batch->school_year,
            $batchMatches[1] ?? $batch->id
        );

        return [$batch, $rows, $filenameBase, $customAllowances];
    }

    public function export(Request $request, string $id)
    {
        [$batch, $rows, $filenameBase, $customAllowances] = $this->payrollExportPayload($id);

        if ($request->query('format') === 'pdf') {
            return Pdf::loadView('exports.payroll_pdf', [
                'batch' => $batch,
                'rows' => $rows,
                'monthLabels' => collect(range(1, 5))->map(fn($month) => "Month {$month}"),
                'customAllowances' => $customAllowances,
            ])
                ->setPaper('legal', 'landscape')
                ->download($filenameBase . '.pdf');
        }

        return Excel::download(new PayrollExport($batch, $rows, $customAllowances), $filenameBase . '.xlsx');
    }

    public function update(Request $request, $id, $type)
    {
        if ($type !== 'status') {
            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Invalid action',
                'message' => 'The requested stipend action is not supported.',
            ]);
        }

        $batchId = Hashids::decode($id)[0] ?? 0;
        $data = $request->validate([
            'status' => ['required', 'in:submitted_payroll,rejected_payroll,approved_payroll'],
            'remarks' => ['required_if:status,rejected_payroll', 'nullable', 'string'],
        ]);

        $batch = Batches::findOrFail($batchId);
        $latestStatus = $batch->logs()->latest('created_at')->value('status') ?? 'draft';
        $permissions = $this->permissions();
        $isAllowed = match ($data['status']) {
            'submitted_payroll' => $permissions->canEditPayroll(Auth::user(), $batch, $latestStatus),
            'approved_payroll' => $permissions->can(Auth::user(), 'payroll.approve')
                && $permissions->canReviewPayroll(Auth::user(), $latestStatus),
            'rejected_payroll' => $permissions->can(Auth::user(), 'payroll.reject')
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

        $batch->logs()->create([
            'status' => $data['status'],
            'remarks' => $data['remarks'] ?? null,
            'action_by' => Auth::user()->profile?->fullname,
        ]);

        $this->syncBatchRecipientTermStatuses($batch, $data['status']);
        $this->syncBatchFinancialStatuses($batch, $data['status']);

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title' => 'Payroll Batch status updated',
            'message' => 'The payroll batch status was updated.',
        ]);
    }

    public function addRecipients(Request $request, string $id): RedirectResponse
    {
        $batchId = Hashids::decode($id)[0] ?? 0;
        $batch = Batches::findOrFail($batchId);
        $latestStatus = $batch->logs()->latest('created_at')->value('status') ?? 'draft';

        if (! $this->permissions()->canEditPayroll(Auth::user(), $batch, $latestStatus)) {
            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Unauthorized',
                'message' => 'You are not allowed to edit this payroll batch.',
            ]);
        }

        $data = $request->validate([
            'scholar_ids' => ['required', 'array', 'min:1'],
            'scholar_ids.*' => ['required', 'string'],
        ]);

        try {
            DB::beginTransaction();
            $addedCount = 0;
            $attachedCount = 0;
            $allowanceDefaults = $this->visibleFixedAllowanceDefaults();
            $allowanceTypeIds = $this->allowanceTypeIds();
            $monthlyLiving = $allowanceDefaults['monthly_living'] ?? 0;
            $totalMonthlyLiving = $monthlyLiving * 5;
            $defaultConnectivity = $allowanceDefaults['connectivity'] ?? 0;
            $defaultClothing = $allowanceDefaults['clothing'] ?? 0;

            foreach ($data['scholar_ids'] as $hashScholarId) {
                $scholarId = Hashids::decode($hashScholarId)[0] ?? null;
                if (!$scholarId) {
                    continue;
                }

                $scholar = Scholars::with(['profile', 'landbank'])->find($scholarId);
                if (!$scholar) {
                    continue;
                }

                $alreadyInSameTermPayroll = BatchRecipients::where('scholar_id', $scholar->id)
                    ->where('batch_id', '!=', $batch->id)
                    ->whereHas('batch', function ($query) use ($batch) {
                        $query->where('region', $batch->region)
                            ->where('school_year', $batch->school_year)
                            ->whereNull('deleted_at');

                        if ($batch->term_id) {
                            $query->where('term_id', $batch->term_id);
                        } else {
                            $query->where('academic_term', $batch->academic_term);
                        }

                        if ($batch->level_id) {
                            $query->where('level_id', $batch->level_id);
                        }
                    })
                    ->exists();

                if ($alreadyInSameTermPayroll) {
                    continue;
                }

                $recipient = BatchRecipients::firstOrCreate(
                    [
                        'batch_id' => $batch->id,
                        'scholar_id' => $scholar->id,
                    ],
                    [
                        'account_no' => $scholar->landbank?->account_number,
                        'birthday' => $scholar->profile?->birthdate,
                        'period' => trim($batch->academic_term . ' AY ' . $batch->school_year),
                        'scholarship_status' => null,
                        'total_stipend' => $totalMonthlyLiving,
                        'learning_materials_amount' => $defaultConnectivity,
                        'clothing_amount' => $defaultClothing,
                        'grand_total' => $totalMonthlyLiving + $defaultConnectivity + $defaultClothing,
                        'status' => 'pending',
                    ]
                );

                if ($recipient->wasRecentlyCreated) {
                    $addedCount++;
                }
                $attachedCount++;

                foreach (range(1, 5) as $month) {
                    $recipient->stipends()->firstOrCreate(
                        ['month_no' => $month],
                        [
                            'month' => 'Month ' . $month,
                            'amount' => $monthlyLiving,
                            'status' => 'pending',
                        ]
                    );
                }

                if ($recipient->wasRecentlyCreated) {
                    foreach ([
                        'connectivity' => [
                            'classification' => 'connectivity',
                            'amount' => $allowanceDefaults['connectivity'] ?? 0,
                        ],
                        'clothing' => [
                            'classification' => 'clothing',
                            'amount' => $allowanceDefaults['clothing'] ?? 0,
                        ],
                    ] as $code => $allowanceData) {
                        if ($allowanceData['amount'] <= 0) {
                            continue;
                        }

                        $recipient->allowances()->create([
                            'allowance_type_id' => $allowanceTypeIds[$code] ?? null,
                            'classification' => $allowanceData['classification'],
                            'amount' => $allowanceData['amount'],
                            'status' => 'pending',
                        ]);
                    }
                }

                $this->updateScholarTermPayrollStatus($batch, $scholar->id, $latestStatus);
            }

            if ($attachedCount === 0) {
                DB::rollBack();

                return redirect()->back()->with('flash', [
                    'status' => 'error',
                    'title' => 'No scholars added',
                    'message' => 'The selected scholars may already be included in this payroll or another payroll for the same term and year.',
                ]);
            }

            DB::commit();

            $message = $addedCount > 0
                ? "{$addedCount} scholar(s) were added to the payroll."
                : 'The selected scholar(s) are already attached to this payroll.';

            return redirect()->back()->with('flash', [
                'status' => 'success',
                'title' => 'Scholars added',
                'message' => $message,
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Failed to add scholars to stipend payroll.', [
                'batch_id' => $batch->id ?? null,
                'scholar_ids' => $data['scholar_ids'] ?? [],
                'message' => $th->getMessage(),
            ]);

            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Something went wrong.',
                'message' => $th->getMessage(),
            ]);
        }
    }

    public function savePayroll(Request $request, string $id): RedirectResponse
    {
        $batchId = Hashids::decode($id)[0] ?? 0;
        $batch = Batches::findOrFail($batchId);
        $latestStatus = $batch->logs()->latest('created_at')->value('status') ?? 'draft';

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
            'recipients.*.custom_allowances' => ['nullable', 'array'],
            'recipients.*.custom_allowances.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->enforceAllowanceMaximums($data['recipients']);

        try {
            DB::beginTransaction();
            $allowanceTypeIds = $this->allowanceTypeIds();

            foreach ($data['recipients'] as $item) {
                $recipientId = Hashids::decode($item['id'])[0] ?? 0;
                $recipient = BatchRecipients::where('batch_id', $batchId)->findOrFail($recipientId);

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
                $customAllowances = collect($item['custom_allowances'] ?? [])
                    ->only($this->customAllowanceCodes())
                    ->map(fn($amount) => (float) ($amount ?? 0));
                $grandTotal = $totalStipend + $totalWithheld + $learningMaterials + $clothing + $customAllowances->sum();

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

                foreach ($this->customAllowanceCodes() as $code) {
                    $amount = (float) ($customAllowances[$code] ?? 0);

                    if ($amount <= 0) {
                        RecipientAllowance::where('recipient_id', $recipient->id)
                            ->where(function ($query) use ($code, $allowanceTypeIds) {
                                $query->where('classification', $code);

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
                            'allowance_type_id' => $allowanceTypeIds[$code] ?? null,
                        ],
                        [
                            'classification' => $code,
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

            DB::commit();

            return redirect()->back()->with('flash', [
                'status' => 'success',
                'title' => 'Payroll saved',
                'message' => 'Payroll informations were saved.',
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
                $latestStatus = $batch->logs()->latest('created_at')->value('status') ?? 'draft';

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
                'message' => 'The requested stipend action is not supported.',
            ]);
        }

            $recipientId = Hashids::decode($id)[0] ?? 0;

            try {
                DB::beginTransaction();

                $recipient = BatchRecipients::findOrFail($recipientId);
                $latestStatus = $recipient->batch?->logs()->latest('created_at')->value('status') ?? 'draft';

                if (! $recipient->batch || ! $this->permissions()->canEditPayroll(Auth::user(), $recipient->batch, $latestStatus)) {
                    DB::rollBack();

                    return redirect()->back()->with('flash', [
                        'status' => 'error',
                        'title' => 'Unauthorized',
                        'message' => 'You are not allowed to edit this payroll batch.',
                    ]);
                }
            $batch = $recipient->batch;
            if ($batch && $recipient->scholar_id) {
                $this->resetScholarTermToApproved($batch, $recipient->scholar_id);
            }

            $recipient->stipends()->delete();
            $recipient->withhelds()->delete();
            $recipient->allowances()->delete();
            $recipient->delete();

            DB::commit();

            return redirect()->back()->with('flash', [
                'status' => 'success',
                'title' => 'Scholar removed',
                'message' => 'The scholar was removed from this payroll.',
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
