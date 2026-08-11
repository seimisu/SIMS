<?php

namespace App\Services\Scholar\Management;

use App\Models\LocationBarangays;
use App\Models\LocationCity;
use App\Models\LocationProvinces;
use App\Models\LocationRegions;
use App\Models\Scholars;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

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
        $input = [
            'email' => $data['email'],
            'contact_no' => $data['contact_no'],
            'civil_status' => $data['civil_status'],
            'address' => $data['address'],
            ...$addressCodes,
        ];

        $changes = array_diff_assoc($input, $previous);

        $scholar->profile()->update([
            'email' => $data['email'],
            'contact_no' => $data['contact_no'],
            'civil_status' => $data['civil_status'],
        ]);

        $scholar->address()->update([
            'address' => $data['address'],
            ...$addressCodes,
        ]);

        $scholar->requestHistory()->create([
            'request_type' => 'profile',
            'previous' => array_intersect_key($previous, $changes),
            'changes' => $changes,
            'created_by' => Auth::user()->profile->fullname,
            'created_at' => now(),
            'request_no' => $data['count'],
        ]);

        $scholar->profileRequest()->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => Auth::user()->profile->fullname,
        ]);
    }

    private function reject(Scholars $scholar, array $data): void
    {
        $scholar->profileRequest()->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'reviewed_by' => Auth::user()->profile->fullname,
            'remarks' => $data['remarks'],
        ]);
    }

    private function addressCodes(array $data): array
    {
        return [
            'barangay_code' => LocationBarangays::firstWhere('name', $data['barangay'])?->code,
            'municipality_code' => LocationCity::firstWhere('name', $data['municipality'])?->code,
            'province_code' => LocationProvinces::firstWhere('name', $data['province'])?->code,
            'region_code' => LocationRegions::firstWhere('region', $data['region'])?->code,
        ];
    }
}
