<?php

namespace App\Services\Payroll;

use App\Models\Batches;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PayrollBatchOptionsService
{
    public function academicYearOptions()
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
            ->filter(fn ($year) => is_string($year) && preg_match('/^\d{4}-\d{4}$/', trim($year)))
            ->map(fn ($year) => trim($year))
            ->unique()
            ->sortByDesc(fn ($year) => (int) Str::before($year, '-'))
            ->take(5)
            ->values()
            ->map(fn ($year) => [
                'id' => $year,
                'name' => $year,
            ]);
    }

    public function statusOptions(): array
    {
        return [
            ['id' => 'draft', 'name' => 'Draft'],
            ['id' => 'submitted_payroll', 'name' => 'Submitted Payroll'],
            ['id' => 'rejected_payroll', 'name' => 'Returned Payroll'],
            ['id' => 'approved_payroll', 'name' => 'Approved Payroll'],
        ];
    }

    public function inputArrayValue(mixed $value, string $key = 'name'): mixed
    {
        if (is_array($value)) {
            return $value[$key] ?? $value['name'] ?? $value['id'] ?? null;
        }

        return $value;
    }

    public function nextBatchNumber(string $region, string $academicYear, int|string|null $termId, ?string $termName): int
    {
        $usedNumbers = Batches::query()
            ->whereNull('deleted_at')
            ->where('school_year', $academicYear)
            ->where('region', $region)
            ->when($termId, fn ($query) => $query->where('term_id', $termId), function ($query) use ($termName) {
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
}
