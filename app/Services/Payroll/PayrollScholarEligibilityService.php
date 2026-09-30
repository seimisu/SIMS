<?php

namespace App\Services\Payroll;

use App\Models\Batches;
use App\Models\ListStatuses;
use App\Models\Scholars;
use App\Support\ScholarStatuses;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PayrollScholarEligibilityService
{
    public function __construct(private readonly PayrollStatusService $payrollStatuses)
    {
    }

    public function isPartialAllowanceStanding(?string $standing): bool
    {
        return Str::lower(trim((string) $standing)) === 'continue with partial allowance';
    }

    public function standingForBatch(Batches $batch, int $scholarId): ?string
    {
        $termIds = $this->payrollStatuses
            ->scholarTermRowsQuery($batch, [$scholarId])
            ->pluck('id');

        if ($termIds->isEmpty()) {
            return null;
        }

        return DB::connection('scholars')
            ->table('scholar_processes')
            ->whereIn('term_record_id', $termIds)
            ->value('scholarship_status');
    }

    public function canJoinPayroll(Scholars $scholar): bool
    {
        $eligibleStatusNames = ScholarStatuses::ELIGIBLE_FOR_PAYROLL;
        $eligibleStatusIds = ListStatuses::where('type', 'scholar')
            ->whereIn(DB::raw('UPPER(name)'), $eligibleStatusNames)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return in_array((int) $scholar->status_id, $eligibleStatusIds, true)
            || in_array(Str::upper(trim($scholar->academic_status ?? '')), $eligibleStatusNames, true);
    }
}
