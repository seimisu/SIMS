<?php

namespace App\Services\Scholar\Management;

use App\Models\ActivityLogs;
use App\Models\Scholars;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ScholarLandbankRequestService
{
    public function decide(string $type, array $data): array
    {
        $scholar = Scholars::where('spas_no', $data['spas_no'])->firstOrFail();
        $request = $scholar->landbankRequest()
            ->where('id', $data['request_id'] ?? null)
            ->first();

        if (! $request || $request->status !== 'pending') {
            return [
                'status' => 'info',
                'title' => 'Already processed',
                'message' => 'This Landbank request was already reviewed.',
            ];
        }

        if ($type === 'accept') {
            $this->approve($scholar, $data);
        } else {
            $validation = Validator::make($data, [
                'reject' => 'required|string|max:255',
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
                ? 'Landbank request approved'
                : 'Landbank request rejected',
            'message' => $type === 'accept'
                ? 'The Landbank change request has been approved.'
                : 'The Landbank change request has been rejected.',
        ];
    }

    private function approve(Scholars $scholar, array $data): void
    {
        $landbank = $scholar->landbank()->first();
        $previous = $landbank ? $landbank->only([
            'account_number',
            'account_name',
            'uploaded_type',
            'uploaded_file',
        ]) : [];

        $input = [
            'account_number' => $data['no'],
            'account_name' => $data['name'],
            'uploaded_type' => $data['type'],
            'uploaded_file' => $data['file'],
            'created_by' => Auth::user()->profile->fullname,
            'updated_by' => Auth::user()->profile->fullname,
        ];

        $filteredInput = collect($input)
            ->except(['created_by', 'updated_by'])
            ->toArray();
        $changes = $landbank
            ? array_diff_assoc($filteredInput, $previous)
            : $input;

        $scholar->landbank()->updateOrCreate([], $input);

        if ($changes !== []) {
            $scholar->requestHistory()->firstOrCreate(
                [
                    'request_type' => 'landbank',
                    'request_no' => $data['count'],
                ],
                [
                    'previous' => $previous,
                    'changes' => $changes,
                    'created_by' => Auth::user()->profile->fullname,
                    'created_at' => now(),
                ]
            );

            ActivityLogs::create([
                'scholar_id' => $scholar->id,
                'previous_data' => array_intersect_key($previous, $changes),
                'changes_data' => $changes,
                'created_by' => Auth::user()->profile->fullname,
                'request_type' => 'landbank',
            ]);
        }

        $scholar->landbankRequest()
            ->where('id', $data['request_id'])
            ->where('status', 'pending')
            ->update([
                'status' => 'approved',
                'reviewed_at' => now(),
                'reviewed_by' => Auth::user()->profile->fullname,
            ]);
    }

    private function reject(Scholars $scholar, array $data): void
    {
        $scholar->landbankRequest()
            ->where('id', $data['request_id'])
            ->where('status', 'pending')
            ->update([
                'status' => 'rejected',
                'reviewed_at' => now(),
                'reviewed_by' => Auth::user()->profile->fullname,
                'reviewer_remarks_encrypted' => $data['reject'],
            ]);
    }
}
