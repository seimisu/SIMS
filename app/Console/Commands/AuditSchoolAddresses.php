<?php

namespace App\Console\Commands;

use App\Models\SchoolCampuses;
use Illuminate\Console\Command;

class AuditSchoolAddresses extends Command
{
    protected $signature = 'schools:audit-addresses';

    protected $description = 'Report non-deleted campuses with missing or inconsistent addresses (read only)';

    public function handle(): int
    {
        $issues = 0;
        SchoolCampuses::where('is_delete', false)
            ->with(['address.barangay.cityCode.provinceCode.regionCode'])
            ->chunkById(200, function ($campuses) use (&$issues) {
                foreach ($campuses as $campus) {
                    $address = $campus->address;
                    $barangay = $address?->barangay;
                    $city = $barangay?->cityCode;
                    $province = $city?->provinceCode;
                    $region = $province?->regionCode;
                    if (! $address || ! $barangay || ! $city || ! $province || ! $region
                        || $barangay->is_delete || $city->is_delete || $province->is_delete || $region->is_delete
                        || $address->municipality_code !== $city->code
                        || $address->province_code !== $province->code
                        || $address->region_code !== $region->code) {
                        $issues++;
                        $this->warn("Campus {$campus->id} (school {$campus->school_id}): ".($address ? 'invalid or inconsistent location references' : 'missing address'));
                    }
                }
            });
        $this->info("Found {$issues} campus address issue(s). No records changed.");

        return $issues > 0 ? self::FAILURE : self::SUCCESS;
    }
}
