<?php

namespace App\Services\Scholar\Management;

use App\Models\Scholars;

class ScholarTransferService
{
    public function transferSchool(int $scholarId, array $data): array
    {
        $scholar = Scholars::findOrFail($scholarId);

        $scholar->schoolInfo()->create([
            'campus_id' => $data['school']['id'],
            'campus_course_id' => $data['course']['id'],
        ]);

        return [
            'status' => 'success',
            'title' => 'Course Transferred',
            'message' => 'Course transfer successful.',
        ];
    }
}
