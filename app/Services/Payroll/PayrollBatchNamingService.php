<?php

namespace App\Services\Payroll;

use App\Models\ListAgencies;
use App\Models\LocationRegions;
use Illuminate\Support\Str;

class PayrollBatchNamingService
{
    public function regionCode(array|string|null $region): string
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

        return 'R'.$this->regionNumber($matches[1]);
    }

    public function selectedAgencyRegion(array|string|null $region): array
    {
        if (is_array($region) && ! empty($region['region_code'])) {
            return ['region_code' => $region['region_code']];
        }

        $agency = null;

        if (is_array($region) && ! empty($region['id'])) {
            $agency = ListAgencies::whereKey($region['id'])->first(['region_code']);
        }

        if (! $agency && is_array($region) && ! empty($region['name'])) {
            $agency = ListAgencies::whereRaw('LOWER(name) = ?', [Str::lower($region['name'])])
                ->first(['region_code']);
        }

        if (! $agency && is_string($region)) {
            $agency = ListAgencies::whereRaw('LOWER(name) = ?', [Str::lower($region)])
                ->first(['region_code']);
        }

        return ['region_code' => $agency?->region_code];
    }

    public function termCode(?string $term): string
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

    public function batchName(array|string|null $region, ?string $term, ?string $academicYear, string|int|null $batch): string
    {
        $region = $this->selectedAgencyRegion($region);

        $years = explode('-', $academicYear ?? '');
        $ay = count($years) === 2
            ? substr($years[0], -2).substr($years[1], -2)
            : Str::of($academicYear ?? '')->replaceMatches('/[^0-9]+/', '')->substr(-4)->toString();
        $batchNo = Str::of((string) $batch)->replaceMatches('/^batch\s*/i', '')->replaceMatches('/[^A-Za-z0-9]+/', '')->toString();

        return $this->regionCode($region)
            .'_'
            .$this->termCode($term)
            .'AY'
            .$ay
            .'_Batch'
            .$batchNo;
    }

    public function regionNumber(string $value): string
    {
        $value = Str::upper(trim($value));
        $value = str_replace('-', '', $value);

        if (preg_match('/^(\d+)([A-Z]?)$/', $value, $matches)) {
            return $matches[1].($matches[2] ?? '');
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

            return $total.$suffix;
        }

        return $value;
    }
}
