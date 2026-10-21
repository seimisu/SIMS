<?php

namespace App\Services\Academic;

use App\Models\Scholars;
use App\Models\ScholarTerm;
use App\Models\SchoolCampusSemesters;
use App\Support\ScholarStatuses;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScholarAcademicPeriodService
{
    public const DUPLICATE_MESSAGE = 'You already have a submission for this academic year and semester. Please open your existing submission. If it was rejected, correct and resubmit that record.';

    public function availableFor(Scholars $scholar): Collection
    {
        if (ScholarStatuses::blocksServices($scholar->academic_status)) {
            return collect();
        }

        $schoolInfo = $scholar->schoolInfo()->latest('id')->first();
        if (! $schoolInfo?->campus_id) {
            return collect();
        }

        return SchoolCampusSemesters::query()
            ->whereHas('campus', fn ($campus) => $campus->notDeleted())
            ->with('semester:id,name')
            ->open()
            ->where('campus_id', $schoolInfo->campus_id)
            ->whereDate('submission_date', '>=', today())
            ->whereDoesntHave('termRecords', fn ($query) => $query->where('scholar_id', $scholar->id))
            ->whereNotExists(fn ($query) => $query->selectRaw('1')
                ->from('scholar_term_records as existing_term')
                ->where('existing_term.scholar_id', $scholar->id)
                ->whereColumn('existing_term.term_id', 'school_campus_semesters.semester_id')
                ->whereRaw('TRIM(existing_term.academic_year) = TRIM(school_campus_semesters.school_year)'))
            ->orderByDesc('school_year')
            ->orderBy('semester_id')
            ->get()
            ->map(fn ($period) => [
                'id' => $period->id,
                'term_id' => $period->semester_id,
                'term_name' => $period->semester?->name,
                'academic_year' => $period->school_year,
                'submission_date' => $period->submission_date?->format('Y-m-d'),
                'name' => trim(($period->semester?->name ?? 'Term').' / AY '.$period->school_year),
            ])
            ->values();
    }

    public function createTermRecord(
        Scholars $scholar,
        SchoolCampusSemesters $period,
        ?int $levelId = null
    ): ScholarTerm {
        if (! $period->campus()->notDeleted()->exists()) {
            throw ValidationException::withMessages(['period' => 'This campus is unavailable for new academic records.']);
        }

        if (ScholarStatuses::blocksServices($scholar->academic_status)) {
            throw ValidationException::withMessages([
                'period' => 'This scholar status is not eligible to submit academic records.',
            ]);
        }

        $schoolInfo = $scholar->schoolInfo()->latest('id')->first();
        if (! $schoolInfo || (int) $schoolInfo->campus_id !== (int) $period->campus_id) {
            throw ValidationException::withMessages([
                'period' => 'The academic period does not belong to the scholar current campus.',
            ]);
        }

        if ($period->status !== SchoolCampusSemesters::STATUS_OPEN || ! $period->is_active || $period->is_delete) {
            throw ValidationException::withMessages(['period' => 'The academic period is not open.']);
        }

        if ($period->submission_date?->isBefore(today())) {
            throw ValidationException::withMessages(['period' => 'The submission deadline has passed.']);
        }

        if (ScholarTerm::forAcademicPeriod($scholar->id, $period->school_year, $period->semester_id)->exists()) {
            throw ValidationException::withMessages(['period' => self::DUPLICATE_MESSAGE]);
        }

        try {
            return DB::connection('pgsql')->transaction(fn () => ScholarTerm::create([
                'scholar_id' => $scholar->id,
                'scholar_school_id' => $schoolInfo->id,
                'campus_semester_id' => $period->id,
                'term_id' => $period->semester_id,
                'term_type_id' => $schoolInfo->campus?->term_id,
                'academic_year' => trim($period->school_year),
                'level_id' => $levelId,
                'specialization_id' => $schoolInfo->specialization_id,
            ]));
        } catch (UniqueConstraintViolationException $exception) {
            // Catch outside the transaction so PostgreSQL has already rolled back.
            if (str_contains($exception->getMessage(), 'scholar_academic_year_term_unique')
                || str_contains($exception->getMessage(), 'scholar_campus_semester_unique')) {
                throw ValidationException::withMessages(['period' => self::DUPLICATE_MESSAGE]);
            }

            throw $exception;
        }
    }
}
