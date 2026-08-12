<?php

namespace App\Services\Scholar\Management;

use App\Models\LocationBarangays;
use App\Models\LocationCity;
use App\Models\LocationProvinces;
use App\Models\LocationRegions;
use App\Models\Scholars;
use App\Models\ActivityLogs;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ScholarProfileRequestService
{
    public function decide(string $type, array $data): array
    {
        $scholar = Scholars::where('spas_no', $data['spas_no'])->firstOrFail();

        if ($type === 'accept') {
            $this->approve($scholar, $data);
        } else {
            $validation = Validator::make($data, [
                'remarks' => 'required|string|max:255',
            ]);

            if ($validation->fails()) {
                return [
                    'status' => 'error',
                    'title' => 'Validation Failed',
                    'message' => 'Please fill in the remarks field.',
                ];
            }

            $this->reject($scholar, $data);
        }

        return [
            'status' => 'success',
            'title' => $type === 'accept'
                ? 'Scholar info request approved'
                : 'Scholar info request rejected',
            'message' => $type === 'accept'
                ? 'The scholar information change request has been approved.'
                : 'The scholar information change request has been rejected.',
        ];
    }

    private function approve(Scholars $scholar, array $data): void
    {
        $profile = $scholar->profile;
        $address = $scholar->address;

        $previous = array_merge(
            $profile ? $profile->only([
                'fname',
                'mname',
                'lname',
                'suffix',
                'email',
                'contact_no',
                'civil_status',
            ]) : [],
            $address ? $address->only([
                'address',
                'barangay_code',
                'municipality_code',
                'province_code',
                'region_code',
            ]) : []
        );

        $addressCodes = $this->addressCodes($data);
        $profileInput = $this->filledChanges([
            'fname' => $data['first_name'] ?? null,
            'mname' => $data['middle_name'] ?? null,
            'lname' => $data['last_name'] ?? null,
            'suffix' => $data['suffix'] ?? null,
            'email' => $data['email'] ?? null,
            'contact_no' => $data['contact_no'] ?? null,
            'civil_status' => $data['civil_status'] ?? null,
        ], ['fname', 'mname', 'lname', 'suffix', 'civil_status']);
        $addressInput = $this->filledChanges([
            'address' => $data['address'] ?? null,
            ...$addressCodes,
        ]);
        $input = [...$profileInput, ...$addressInput];

        $changes = array_diff_assoc($input, $previous);

        if ($profileInput !== []) {
            $scholar->profile()->update($profileInput);
        }

        if ($addressInput !== []) {
            $scholar->address()->update($addressInput);
        }

        $scholar->requestHistory()->create([
            'request_type' => 'profile',
            'previous' => array_intersect_key($previous, $changes),
            'changes' => $changes,
            'created_by' => Auth::user()->profile->fullname,
            'created_at' => now(),
            'request_no' => $data['count'],
        ]);

        if ($changes !== []) {
            ActivityLogs::create([
                'scholar_id' => $scholar->id,
                'previous_data' => array_intersect_key($previous, $changes),
                'changes_data' => $changes,
                'created_by' => Auth::user()->profile->fullname,
                'request_type' => 'profile',
            ]);
        }

        $scholar->profileRequest()
            ->where('id', $data['request_id'] ?? null)
            ->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => Auth::user()->profile->fullname,
        ]);
    }

    private function reject(Scholars $scholar, array $data): void
    {
        $scholar->profileRequest()
            ->where('id', $data['request_id'] ?? null)
            ->update([
                'status' => 'rejected',
                'reviewed_at' => now(),
                'reviewed_by' => Auth::user()->profile->fullname,
                'reviewer_remarks_encrypted' => $data['remarks'],
            ]);
    }

    private function addressCodes(array $data): array
    {
        return [
            'barangay_code' => isset($data['barangay']) ? LocationBarangays::firstWhere('name', $data['barangay'])?->code : null,
            'municipality_code' => isset($data['municipality']) ? LocationCity::firstWhere('name', $data['municipality'])?->code : null,
            'province_code' => isset($data['province']) ? LocationProvinces::firstWhere('name', $data['province'])?->code : null,
            'region_code' => isset($data['region']) ? LocationRegions::firstWhere('region', $data['region'])?->code : null,
        ];
    }

    private function filledChanges(array $input, array $uppercaseKeys = []): array
    {
        return collect($input)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value, $key) => in_array($key, $uppercaseKeys, true) ? Str::upper($value) : $value)
            ->all();
    }
}
