<?php

namespace App\Services\Academic;

use App\Models\SchoolCampuses;
use App\Models\SchoolCampusSemesters;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CampusAcademicPeriodService
{
    public function create(SchoolCampuses $campus, array $data, int $userId): SchoolCampusSemesters
    {
        return DB::transaction(function () use ($campus, $data, $userId) {
            $status = $data['status'] ?? SchoolCampusSemesters::STATUS_DRAFT;

            if ($status === SchoolCampusSemesters::STATUS_OPEN) {
                $this->closeCurrentPeriod($campus, $userId);
            }

            return $campus->semesters()->create([
                'semester_id' => $data['semesterId'],
                'school_year' => $data['schoolYear'],
                'submission_date' => $data['submissionDate'],
                'status' => $status,
                'opened_at' => $status === SchoolCampusSemesters::STATUS_OPEN ? now() : null,
                'opened_by' => $status === SchoolCampusSemesters::STATUS_OPEN ? $userId : null,
                'is_active' => true,
                'is_delete' => false,
            ]);
        });
    }

    public function changeStatus(
        SchoolCampusSemesters $period,
        string $status,
        int $userId
    ): SchoolCampusSemesters {
        if (! in_array($status, [SchoolCampusSemesters::STATUS_OPEN, SchoolCampusSemesters::STATUS_CLOSED], true)) {
            throw ValidationException::withMessages(['status' => 'The academic period status is invalid.']);
        }

        return DB::transaction(function () use ($period, $status, $userId) {
            if ($status === SchoolCampusSemesters::STATUS_OPEN) {
                $this->closeCurrentPeriod($period->campus, $userId, $period->id);
                $period->update([
                    'status' => SchoolCampusSemesters::STATUS_OPEN,
                    'opened_at' => now(),
                    'opened_by' => $userId,
                    'closed_at' => null,
                    'closed_by' => null,
                    'is_active' => true,
                ]);
            } else {
                $period->update([
                    'status' => SchoolCampusSemesters::STATUS_CLOSED,
                    'closed_at' => now(),
                    'closed_by' => $userId,
                ]);
            }

            return $period->refresh();
        });
    }

    private function closeCurrentPeriod(SchoolCampuses $campus, int $userId, ?int $exceptId = null): void
    {
        $campus->semesters()
            ->open()
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->update([
                'status' => SchoolCampusSemesters::STATUS_CLOSED,
                'closed_at' => now(),
                'closed_by' => $userId,
            ]);
    }
}
