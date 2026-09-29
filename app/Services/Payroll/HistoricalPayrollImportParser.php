<?php

namespace App\Services\Payroll;

use App\Models\ListReferences;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class HistoricalPayrollImportParser
{
    public function rows(string $path)
    {
        $sheet = IOFactory::load($path)->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();
        $rows = collect();

        for ($row = 6; $row <= $highestRow; $row++) {
            $spasNo = trim((string) $this->cellValue($sheet, 1, $row));
            $period = trim((string) $this->cellValue($sheet, 7, $row));

            if ($spasNo === '' && $period === '') {
                continue;
            }

            if (in_array(Str::upper($period), ['SUB-TOTAL', 'TOTAL'], true)) {
                continue;
            }

            if ($spasNo === '' || Str::startsWith(Str::upper($spasNo), ['PREPARED', 'NOTED', 'CERTIFIED', 'THIS IS'])) {
                continue;
            }

            [$term, $academicYear] = $this->parsePayrollPeriod($period);
            if (! $academicYear) {
                throw ValidationException::withMessages([
                    'payroll_file' => ["Unable to read academic year from row {$row} period: {$period}."],
                ]);
            }

            $rows->push([
                'spas_no' => Str::upper($spasNo),
                'account_no' => trim((string) $this->cellValue($sheet, 2, $row)),
                'name' => trim((string) $this->cellValue($sheet, 3, $row)),
                'program' => trim((string) $this->cellValue($sheet, 4, $row)),
                'university' => trim((string) $this->cellValue($sheet, 5, $row)),
                'scholarship_status' => trim((string) $this->cellValue($sheet, 6, $row)),
                'period' => $period,
                'term' => $term ?: $period,
                'academic_year' => $academicYear,
                'month_1' => $this->moneyValue($this->cellValue($sheet, 8, $row)),
                'month_2' => $this->moneyValue($this->cellValue($sheet, 9, $row)),
                'month_3' => $this->moneyValue($this->cellValue($sheet, 10, $row)),
                'month_4' => $this->moneyValue($this->cellValue($sheet, 11, $row)),
                'month_5' => $this->moneyValue($this->cellValue($sheet, 12, $row)),
                'total_withheld' => $this->moneyValue($this->cellValue($sheet, 13, $row)),
                'remarks' => trim((string) $this->cellValue($sheet, 14, $row)),
                'learning_materials_amount' => $this->moneyValue($this->cellValue($sheet, 15, $row)),
                'clothing_amount' => $this->moneyValue($this->cellValue($sheet, 16, $row)),
                'grand_total' => $this->moneyValue($this->cellValue($sheet, 17, $row)),
            ]);
        }

        return $rows;
    }

    public function termIdFromName(?string $term): ?int
    {
        if (! $term) {
            return null;
        }

        $normalized = Str::lower(trim($term));

        return ListReferences::where('is_active', true)
            ->where('is_delete', false)
            ->whereRaw("TRIM(type) = 'Term'")
            ->get()
            ->first(fn ($reference) => Str::contains($normalized, Str::lower($reference->name)))
            ?->id;
    }

    public function cellValue($sheet, int $column, int $row): mixed
    {
        return $sheet->getCell(Coordinate::stringFromColumnIndex($column).$row)->getCalculatedValue();
    }

    public function moneyValue(mixed $value): float
    {
        $normalized = trim((string) $value);
        if ($normalized === '' || $normalized === '-') {
            return 0.0;
        }

        return (float) str_replace([',', 'â‚±', 'PHP', 'php'], '', $normalized);
    }

    public function parsePayrollPeriod(string $period): array
    {
        preg_match('/(20\d{2}\s*[-â€“]\s*20\d{2})/', $period, $yearMatch);
        $academicYear = isset($yearMatch[1])
            ? str_replace([' ', 'â€“'], ['', '-'], $yearMatch[1])
            : null;
        $term = trim(str_ireplace(['AY', $academicYear], '', $period));
        $term = trim($term, " \t\n\r\0\x0B/-");
        $term = trim(preg_replace('/\s+/', ' ', $term));

        return [$term, $academicYear];
    }
}
