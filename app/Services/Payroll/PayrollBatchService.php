<?php

namespace App\Services\Payroll;

use App\Models\Batches;
use Illuminate\Support\Str;

class PayrollBatchService
{
    public function __construct(
        private readonly PayrollActivityService $activities,
        private readonly PayrollBatchNamingService $names,
        private readonly PayrollBatchOptionsService $options
    ) {
    }

    public function acceptingBatch(
        string $region,
        string $academicYear,
        int|string|null $termId,
        ?string $termName,
        int $batchScholarLimit,
        ?int $excludeBatchId = null
    ): ?Batches {
        return Batches::query()
            ->whereNull('deleted_at')
            ->where('school_year', $academicYear)
            ->where('region', $region)
            ->where('status', 'draft')
            ->when($excludeBatchId, fn ($query) => $query->where('id', '!=', $excludeBatchId))
            ->when($termId, fn ($query) => $query->where('term_id', $termId), function ($query) use ($termName) {
                $query->whereRaw('LOWER(academic_term) = ?', [Str::lower($termName)]);
            })
            ->whereRaw(
                '(SELECT COUNT(*) FROM batch_recipients WHERE batch_recipients.batch_id = batches.id) < ?',
                [$batchScholarLimit]
            )
            ->orderByDesc('created_at')
            ->first();
    }

    public function activeRecipientCount(Batches $batch): int
    {
        return $batch->recipients()->count();
    }

    public function createAutoBatch(
        string $region,
        string $academicYear,
        int|string|null $termId,
        ?string $termName,
        string $remarks
    ): Batches {
        $batchNo = $this->options->nextBatchNumber($region, $academicYear, $termId, $termName);
        $batch = Batches::create([
            'region' => $region,
            'academic_term' => $termName,
            'term_id' => $termId,
            'school_year' => $academicYear,
            'name' => $this->names->batchName($region, $termName, $academicYear, $batchNo),
            'status' => 'draft',
        ]);

        $batch->logs()->create([
            'status' => 'draft',
            'action_by' => $region,
        ]);

        $this->activities->log($batch, 'batch_created', remarks: $remarks);

        return $batch;
    }
}
