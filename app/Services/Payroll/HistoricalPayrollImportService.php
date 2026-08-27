<?php

namespace App\Services\Payroll;

use App\Models\AllowanceType;
use App\Models\BatchRecipients;
use App\Models\Batches;
use App\Models\RecipientAllowance;
use App\Models\RecipientStipend;
use App\Models\RecipientWithheld;
use App\Models\Scholars;
use App\Models\ScholarTerm;
use App\Support\SystemPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class HistoricalPayrollImportService
{
    public function __construct(
        private readonly HistoricalPayrollImportParser $parser,
        private readonly PayrollActivityService $activities,
        private readonly PayrollBatchNamingService $names,
        private readonly SystemPermissions $permissions
    ) {
    }

    public function validateImport(Request $request): array
    {
        $data = $request->validate([
            'payroll_file' => [
                'required',
                'file',
                'mimes:xlsx,xls',
                'max:10240',
            ],
        ]);

        $file = $data['payroll_file'];
        $rows = $this->parser->rows($file->getRealPath());

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'payroll_file' => ['No payroll recipient rows were found in the uploaded Excel file.'],
            ]);
        }

        $spasNumbers = $rows->pluck('spas_no')->filter()->unique()->values();
        $scholars = Scholars::with([
            'profile:scholar_id,fname,mname,lname,suffix',
            'program:id,name',
            'schoolInfo' => fn ($query) => $query
                ->select('id', 'scholar_id', 'campus_id')
                ->latest('id')
                ->with('campus:id,name,generated_name,agency_id'),
            'schoolInfo.campus.agency:id,name,slug,region_code',
        ])
            ->whereIn('spas_no', $spasNumbers)
            ->get()
            ->keyBy(fn ($scholar) => Str::upper(trim($scholar->spas_no)));

        $errors = [];
        $seenAcademicTermRows = [];
        foreach ($rows as $row) {
            $scholar = $scholars->get(Str::upper($row['spas_no']));
            if (! $scholar) {
                $errors[] = "SPAS {$row['spas_no']} was not found.";
                continue;
            }

            $academicTermKey = Str::upper($row['spas_no']).'|'.Str::upper($row['academic_year']).'|'.Str::upper($row['term']);
            if (isset($seenAcademicTermRows[$academicTermKey])) {
                $errors[] = "SPAS {$row['spas_no']} appears more than once for {$row['term']} {$row['academic_year']}.";
                continue;
            }
            $seenAcademicTermRows[$academicTermKey] = true;

            if ($this->permissions->shouldScopeToRegion(Auth::user())) {
                $agencyId = $scholar->schoolInfo->first()?->campus?->agency_id;
                if ($agencyId !== Auth::user()?->profile?->agency_id) {
                    $errors[] = "SPAS {$row['spas_no']} is outside your assigned region.";
                }
            }

            $duplicate = BatchRecipients::where('scholar_id', $scholar->id)
                ->whereHas('batch', function ($query) use ($row) {
                    $query->where('source', 'imported')
                        ->where('is_historical', true)
                        ->where('school_year', $row['academic_year'])
                        ->where(function ($termQuery) use ($row) {
                            $termQuery->where('academic_term', $row['term'])
                                ->orWhere('academic_term', $row['period']);
                        });
                })
                ->exists();

            if ($duplicate) {
                $errors[] = "SPAS {$row['spas_no']} already has a historical payroll for {$row['term']} {$row['academic_year']}.";
            }

            if (blank($row['scholarship_status'] ?? null)) {
                $errors[] = "SPAS {$row['spas_no']} is missing a scholarship standing for {$row['term']} {$row['academic_year']}.";
                continue;
            }

            $termRecord = $this->termRecordForRow($scholar, $row);
            if (! $termRecord) {
                $errors[] = "SPAS {$row['spas_no']} has not yet created academic history for {$row['term']} {$row['academic_year']}.";
                continue;
            }

            $process = $this->scholarProcessForTerm($termRecord->id);
            if (! $process) {
                $errors[] = "SPAS {$row['spas_no']} has not yet created academic history for {$row['term']} {$row['academic_year']}.";
                continue;
            }

            if (filled($process->scholarship_status ?? null)) {
                $errors[] = "SPAS {$row['spas_no']} already has a payroll record for {$row['term']} {$row['academic_year']}.";
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages([
                'payroll_file' => [collect($errors)->take(5)->implode(' ')],
            ]);
        }

        return [$file, $rows, $scholars];
    }

    public function previewPayload($file, $rows, $scholars): array
    {
        return [
            'file_name' => $file->getClientOriginalName(),
            'row_count' => $rows->count(),
            'batch_count' => $rows->groupBy(fn ($row) => $row['academic_year'].'|'.$row['term'])->count(),
            'grand_total' => number_format($rows->sum('grand_total'), 2),
            'total_stipend' => number_format(
                $rows->sum(fn ($row) => collect(range(1, 5))->sum(fn ($month) => (float) $row["month_{$month}"])),
                2
            ),
            'total_withheld' => number_format($rows->sum('total_withheld'), 2),
            'total_allowances' => number_format(
                $rows->sum('learning_materials_amount') + $rows->sum('clothing_amount'),
                2
            ),
            'periods' => $rows
                ->groupBy(fn ($row) => $row['academic_year'].'|'.$row['term'])
                ->map(fn ($group) => [
                    'period' => $group->first()['period'],
                    'term' => $group->first()['term'],
                    'academic_year' => $group->first()['academic_year'],
                    'recipient_count' => $group->count(),
                    'grand_total' => number_format($group->sum('grand_total'), 2),
                ])
                ->values(),
            'sample_rows' => $rows
                ->take(5)
                ->map(function ($row) use ($scholars) {
                    $scholar = $scholars->get(Str::upper($row['spas_no']));

                    return [
                        'spas_no' => $row['spas_no'],
                        'name' => $row['name'],
                        'matched_name' => trim(collect([
                            $scholar?->profile?->lname,
                            $scholar?->profile?->fname,
                            $scholar?->profile?->mname,
                            $scholar?->profile?->suffix,
                        ])->filter()->join(' ')),
                        'period' => $row['period'],
                        'grand_total' => number_format($row['grand_total'], 2),
                    ];
                })
                ->values(),
            'rows' => $rows
                ->values()
                ->map(fn ($row, $index) => [
                    'id' => $index + 1,
                    'account_no' => $row['account_no'],
                    'name' => $row['name'],
                    'program' => $row['program'],
                    'university' => $row['university'],
                    'scholarship_status' => Str::upper($row['scholarship_status'] ?? ''),
                    'period' => $row['period'],
                    'month_1' => (float) $row['month_1'],
                    'month_2' => (float) $row['month_2'],
                    'month_3' => (float) $row['month_3'],
                    'month_4' => (float) $row['month_4'],
                    'month_5' => (float) $row['month_5'],
                    'total_withheld' => (float) $row['total_withheld'],
                    'remarks' => $row['remarks'],
                    'learning_materials_amount' => (float) $row['learning_materials_amount'],
                    'clothing_amount' => (float) $row['clothing_amount'],
                    'grand_total' => (float) $row['grand_total'],
                ]),
        ];
    }

    public function store($rows, $scholars, string $storedPath, string $originalFileName, ?string $actorName): array
    {
        $allowanceTypes = AllowanceType::whereIn('code', ['connectivity', 'clothing'])
            ->get()
            ->keyBy('code');
        $createdBatches = 0;
        $createdRecipients = 0;
        $periodGroups = $rows->groupBy(fn ($row) => $row['academic_year'].'|'.$row['term']);

        foreach ($periodGroups as $groupRows) {
            $firstRow = $groupRows->first();
            $firstScholar = $scholars->get(Str::upper($firstRow['spas_no']));
            $agency = $firstScholar?->schoolInfo->first()?->campus?->agency;
            $region = $agency?->name ?? 'Imported Region';
            $term = $firstRow['term'];
            $academicYear = $firstRow['academic_year'];

            $batch = Batches::create([
                'name' => $this->names->batchName($region, $term, $academicYear, 'HIST'),
                'region' => $region,
                'academic_term' => $term,
                'term_id' => $this->parser->termIdFromName($term),
                'school_year' => $academicYear,
                'is_lock' => true,
                'status' => 'approved_payroll',
                'source' => 'imported',
                'is_historical' => true,
                'imported_by' => $actorName,
                'imported_at' => now(),
                'import_file_path' => $storedPath,
                'import_file_name' => $originalFileName,
            ]);

            $batch->logs()->create([
                'status' => 'approved_payroll',
                'remarks' => 'Historical payroll imported from Excel.',
                'action_by' => $actorName,
            ]);

            foreach ($groupRows as $row) {
                $scholar = $scholars->get(Str::upper($row['spas_no']));
                $totalStipend = collect(range(1, 5))
                    ->sum(fn ($month) => (float) $row["month_{$month}"]);

                $recipient = BatchRecipients::create([
                    'batch_id' => $batch->id,
                    'scholar_id' => $scholar->id,
                    'account_no' => $row['account_no'],
                    'period' => $row['period'],
                    'scholarship_status' => Str::upper($row['scholarship_status'] ?? ''),
                    'total_stipend' => $totalStipend,
                    'total_withheld' => $row['total_withheld'],
                    'learning_materials_amount' => $row['learning_materials_amount'],
                    'clothing_amount' => $row['clothing_amount'],
                    'grand_total' => $row['grand_total'],
                    'remarks' => $row['remarks'],
                    'status' => 'approved',
                ]);

                foreach (range(1, 5) as $month) {
                    RecipientStipend::create([
                        'recipient_id' => $recipient->id,
                        'amount' => $row["month_{$month}"],
                        'month' => "Month {$month}",
                        'month_no' => $month,
                        'status' => 'approved',
                    ]);
                }

                if ((float) $row['total_withheld'] > 0 || trim((string) $row['remarks']) !== '') {
                    RecipientWithheld::create([
                        'recipient_id' => $recipient->id,
                        'month_no' => null,
                        'total_amount' => $row['total_withheld'],
                        'remarks' => $row['remarks'],
                        'status' => 'approved',
                    ]);
                }

                foreach ([
                    'connectivity' => $row['learning_materials_amount'],
                    'clothing' => $row['clothing_amount'],
                ] as $code => $amount) {
                    RecipientAllowance::create([
                        'recipient_id' => $recipient->id,
                        'allowance_type_id' => $allowanceTypes->get($code)?->id,
                        'classification' => $code,
                        'amount' => $amount,
                        'status' => 'approved',
                    ]);
                }

                $this->attachStandingToAcademicHistory($scholar, $row, $actorName);
                $createdRecipients++;
            }

            $this->activities->log(
                $batch,
                'payroll_imported',
                remarks: 'Historical payroll imported from Excel.',
                metadata: [
                    'file_name' => $originalFileName,
                    'recipient_count' => $groupRows->count(),
                ]
            );

            $createdBatches++;
        }

        return [$createdBatches, $createdRecipients];
    }

    private function termRecordForRow(Scholars $scholar, array $row): ?ScholarTerm
    {
        $termId = $this->parser->termIdFromName($row['term'] ?? null);

        return ScholarTerm::query()
            ->where('scholar_id', $scholar->id)
            ->where('academic_year', $row['academic_year'])
            ->when(
                $termId,
                fn ($query) => $query->where('term_id', $termId),
                fn ($query) => $query->whereHas('term', function ($termQuery) use ($row) {
                    $termQuery->whereRaw('LOWER(name) = ?', [Str::lower($row['term'])]);
                })
            )
            ->first();
    }

    private function scholarProcessForTerm(int $termRecordId): ?object
    {
        return DB::connection('scholars')
            ->table('scholar_processes')
            ->where('term_record_id', $termRecordId)
            ->first();
    }

    private function attachStandingToAcademicHistory(Scholars $scholar, array $row, ?string $actorName): void
    {
        $termRecord = $this->termRecordForRow($scholar, $row);
        $process = $termRecord ? $this->scholarProcessForTerm($termRecord->id) : null;

        if (! $termRecord || ! $process) {
            throw ValidationException::withMessages([
                'payroll_file' => ["SPAS {$row['spas_no']} has not yet created academic history for {$row['term']} {$row['academic_year']}."],
            ]);
        }

        if (filled($process->scholarship_status ?? null)) {
            throw ValidationException::withMessages([
                'payroll_file' => ["SPAS {$row['spas_no']} already has a payroll record for {$row['term']} {$row['academic_year']}."],
            ]);
        }

        $scholarshipStatus = Str::upper($row['scholarship_status']);

        DB::connection('scholars')
            ->table('scholar_processes')
            ->where('term_record_id', $termRecord->id)
            ->update([
                'scholar_id' => $scholar->id,
                'scholarship_status' => $scholarshipStatus,
                'submission' => 'APPROVED',
                'payroll' => 'APPROVED',
                'is_end' => false,
                'updated_at' => now(),
                'updated_by' => $actorName,
            ]);

        if ($scholarshipStatus === 'TERMINATED') {
            $scholar->update([
                'academic_status' => 'TERMINATED',
                'updated_at' => now(),
            ]);
        }
    }
}
