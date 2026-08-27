<?php

namespace App\Services\Scholar\Management;

use App\Models\ActivityLogs;
use App\Models\Scholars;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ScholarManagementUpdateService
{
    public function updatePersonal(int $scholarId, array $data): array
    {
        $scholar = Scholars::findOrFail($scholarId);
        $slice = isset($data['fulladdress']['id'])
            ? explode('-', $data['fulladdress']['id'])
            : null;
        $sliceCurrent = isset($data['fulladdressCurrent']['id'])
            ? explode('-', $data['fulladdressCurrent']['id'])
            : null;

        $scholar->update([
            'program_id' => $data['program']['id'],
            'type_id' => $data['sub_program']['id'],
            'award_year' => Carbon::parse($data['award_year'])->format('Y') + 1,
            'academic_status' => Str::upper($data['status']['name'] ?? $data['status']['id'] ?? 'NEW'),
        ]);

        $this->updateProfile($scholar, $scholarId, $data);
        $this->updateAddress($scholar, $scholarId, $data, $slice);
        $this->updateAddressCurrent($scholar, $scholarId, $data, $sliceCurrent);
        $this->updateSchool($scholar, $scholarId, $data);
        $this->updateLandbank($scholar, $scholarId, $data);
        $this->updateGuardian($scholar, $data);

        return [
            'status' => 'success',
            'title' => 'Scholar Updated',
            'message' => 'Scholar information successfully updated.',
        ];
    }

    private function updateProfile(Scholars $scholar, int $scholarId, array $data): void
    {
        $profile = $scholar->profile()->updateOrCreate(
            ['scholar_id' => $scholar->id],
            [
                'fname' => Str::upper($data['first_name']),
                'mname' => Str::upper($data['middle_name']) ?? null,
                'lname' => Str::upper($data['last_name']),
                'suffix' => Str::upper($data['suffix']) ?? null,
                'email' => $data['email'],
                'contact_no' => $data['contact_no'] ?? null,
                'birthplace' => $data['birth_place'] ?? null,
                'birthdate' => Carbon::parse($data['birth_date'])->setTimezone('Asia/Manila')
                    ->format('Y-m-d'),
                'religion' => Str::upper($data['religion']) ?? null,
                'civil_status' => Str::upper($data['civil_status']) ?? null,
            ]
        );

        if ($profile->wasChanged()) {
            $this->logChange($scholarId, 'profile', $profile->getPrevious(), $profile->getChanges());
        }
    }

    private function updateAddress(Scholars $scholar, int $scholarId, array $data, ?array $slice): void
    {
        $addressData = [
            'address' => $data['address'] ?? null,
        ];

        if ($slice) {
            $addressData = [
                ...$addressData,
                'barangay_code' => $slice[0] ?? null,
                'municipality_code' => $slice[1] ?? null,
                'province_code' => $slice[2] ?? null,
                'region_code' => $slice[3] ?? null,
            ];
        }

        $address = $scholar->address()->updateOrCreate(
            ['scholar_id' => $scholar->id],
            $addressData
        );

        if ($address->wasChanged()) {
            $this->logChange($scholarId, 'address', $address->getPrevious(), $address->getChanges());
        }
    }

    private function updateAddressCurrent(Scholars $scholar, int $scholarId, array $data, ?array $slice): void
    {
        $addressData = [
            'address' => $data['addressCurrent'] ?? null,
        ];

        if ($slice) {
            $addressData = [
                ...$addressData,
                'barangay_code' => $slice[0] ?? null,
                'municipality_code' => $slice[1] ?? null,
                'province_code' => $slice[2] ?? null,
                'region_code' => $slice[3] ?? null,
            ];
        }

        $address = $scholar->addressCurrent()->updateOrCreate(
            ['scholar_id' => $scholar->id],
            $addressData
        );

        if ($address->wasChanged()) {
            $this->logChange($scholarId, 'current address', $address->getPrevious(), $address->getChanges());
        }
    }

    private function updateSchool(Scholars $scholar, int $scholarId, array $data): void
    {
        $school = $scholar->schoolInfo()->updateOrCreate(
            [
                'id' => $data['schoolId'],
                'scholar_id' => $scholar->id,
            ],
            [
                'campus_id' => $data['school']['id'],
                'campus_course_id' => $data['course']['id'],
                'curriculum_id' => $data['curriculum']['id'] ?? null,
            ]
        );

        if ($school->wasChanged()) {
            $changes = Arr::except($school->getChanges(), ['updated_at']);
            $previous = Arr::only($school->getOriginal(), array_keys($changes));
            $this->logChange($scholarId, 'school', $previous, $changes);
        }
    }

    private function updateLandbank(Scholars $scholar, int $scholarId, array $data): void
    {
        $isMaskedName = ($data['acc_name'] ?? null) === '**********************';
        $isMaskedNo = ($data['acc_no'] ?? null) === '**********************';

        if ($isMaskedName || $isMaskedNo) {
            return;
        }

        $landbank = $scholar->landbank()->updateOrCreate(
            ['scholar_id' => $scholar->id],
            [
                'account_name' => $data['acc_name'] ?? null,
                'account_number' => $data['acc_no'] ?? null,
            ]
        );

        if ($landbank->wasChanged()) {
            $changes = Arr::except($landbank->getChanges(), ['updated_at']);
            $previous = Arr::except($landbank->getPrevious(), ['updated_at']);
            $this->logChange($scholarId, 'landbank', $previous, $changes);
        }
    }

    private function updateGuardian(Scholars $scholar, array $data): void
    {
        $scholar->parent()->updateOrCreate(
            ['scholar_id' => $scholar->id],
            [
                'fname' => $data['guardian_name'] ?? null,
                'id_no' => $data['guardian_id_no'] ?? null,
                'id_place' => $data['guardian_place_issue'] ?? null,
                'id_date' => $data['guardian_date_issue'] ?? null,
            ]
        );
    }

    private function logChange(int $scholarId, string $type, array $previous, array $changes): void
    {
        ActivityLogs::create([
            'previous_data' => $previous,
            'changes_data' => $changes,
            'request_type' => $type,
            'created_by' => Auth::user()->profile->fullname,
            'scholar_id' => $scholarId,
        ]);
    }
}
