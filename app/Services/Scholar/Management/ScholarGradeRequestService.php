<?php

namespace App\Services\Scholar\Management;

use App\Http\Controllers\Web\PayrollController;
use App\Models\ScholarTerm;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ScholarGradeRequestService
{
    public function decide(string $type, array $data): array
    {
        if ($type === 'accept') {
            $validation = Validator::make($data[0], [
                'scholarshipStatus' => 'required|array|max:255',
            ]);

            if ($validation->fails()) {
                return [
                    'status' => 'error',
                    'title' => 'Validation Failed',
                    'message' => 'Please fill in the scholarship status.',
                ];
            }

            $this->approve($data);
        } else {
            $validation = Validator::make($data[0], [
                'remarks' => 'required|string|max:255',
            ]);

            if ($validation->fails()) {
                return [
                    'status' => 'error',
                    'title' => 'Validation Failed',
                    'message' => 'Please fill in the remarks field.',
                ];
            }

            $this->reject($data);
        }

        return [
            'status' => 'success',
            'title' => $type === 'accept'
                ? 'Grade request approved'
                : 'Grade request rejected',
            'message' => $type === 'accept'
                ? 'The grade request has been approved.'
                : 'The grade request has been rejected.',
        ];
    }

    private function approve(array $data): void
    {
        $scholarshipStatus = $data[0]['scholarshipStatus']['name']
            ?? $data[0]['scholarshipStatus']['id']
            ?? null;
        $scholarshipStatus = Str::upper($scholarshipStatus);

        $terms = $this->terms($data);

        foreach ($terms as $term) {
            DB::table('scholar_term_records')
                ->where('id', $term->id)
                ->update([
                    'verification_status' => 'approved',
                    'verified_by' => Auth::id(),
                    'updated_at' => now(),
                ]);

            $term->forceFill([
                'verification_status' => 'approved',
                'verified_by' => Auth::id(),
            ]);

            DB::connection('scholars')
                ->table('scholar_processes')
                ->updateOrInsert(
                    ['term_record_id' => $term->id],
                    [
                        'scholar_id' => $term->scholar_id,
                        'scholarship_status' => $scholarshipStatus,
                        'submission' => 'APPROVED',
                        'payroll' => 'NOT SUBMITTED',
                        'is_end' => false,
                        'updated_at' => now(),
                        'updated_by' => Auth::user()->profile->fullname,
                    ]
                );

            app(PayrollController::class)->autoAttachApprovedTerm($term->fresh());
        }
    }

    private function reject(array $data): void
    {
        $remarks = collect($data)->firstWhere('status', 'submitted')['remarks'];
        $terms = $this->terms($data);

        foreach ($terms as $term) {
            DB::table('scholar_term_records')
                ->where('id', $term->id)
                ->update([
                    'verification_status' => 'rejected',
                    'rejection_reason' => $remarks,
                    'verified_by' => Auth::id(),
                    'updated_at' => now(),
                ]);

            $term->forceFill([
                'verification_status' => 'rejected',
                'rejection_reason' => $remarks,
                'verified_by' => Auth::id(),
            ]);

            DB::connection('scholars')
                ->table('scholar_processes')
                ->updateOrInsert(
                    ['term_record_id' => $term->id],
                    [
                        'scholar_id' => $term->scholar_id,
                        'submission' => 'REJECTED',
                        'payroll' => 'NOT SUBMITTED',
                        'is_end' => false,
                        'updated_at' => now(),
                        'updated_by' => Auth::user()->profile->fullname,
                    ]
                );
        }
    }

    private function terms(array $data)
    {
        return ScholarTerm::with('scholar:id,spas_no')
            ->whereIn('id', collect($data)->pluck('id'))
            ->get();
    }
}
