<?php

namespace App\Services\Scholar\Submission;

use App\Models\ScholarAcademicHistorySubmission;
use App\Models\ScholarSchoolGrades;
use App\Models\ScholarTerm;
use App\Models\SchoolCampusGrades;
use App\Support\SystemPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Vinkla\Hashids\Facades\Hashids;


class ScholarSubmissionDecisionService
{
    public function academicHistoryDecision(string $id, string $type, Request $request)
    {
        if (! in_array($type, ['approve', 'return'], true)) {
            abort(404);
        }

        $permission = $type === 'approve' ? 'grade-submissions.approve' : 'grade-submissions.reject';
        if (! app(SystemPermissions::class)->can(Auth::user(), $permission)) {
            abort(403, 'Unauthorized');
        }

        $submissionId = Hashids::decode($id)[0] ?? 0;
        $submission = ScholarAcademicHistorySubmission::with([
            'terms.subjects',
            'scholar.schoolInfo',
        ])->findOrFail($submissionId);

        if ($type === 'return') {
            $data = $request->validate([
                'return_reason' => ['required', 'string', 'max:1000'],
            ]);

            $submission->update([
                'status' => 'returned',
                'reviewed_at' => now(),
                'reviewed_by' => Auth::id(),
                'return_reason' => $data['return_reason'],
            ]);

            return redirect()->back()->with('flash', [
                'status' => 'success',
                'title' => 'Academic history returned',
                'message' => 'The academic history submission was returned to the scholar.',
            ]);
        }

        $data = $request->validate([
            'terms' => ['required', 'array', 'min:1'],
            'terms.*.term_id' => ['required', 'integer'],
            'terms.*.scholarshipStatus' => ['required', 'array'],
        ]);

        $termStatuses = collect($data['terms'])->mapWithKeys(function ($term) {
            $status = $term['scholarshipStatus']['name']
                ?? $term['scholarshipStatus']['id']
                ?? null;

            return [$term['term_id'] => $status ? Str::upper($status) : null];
        });

        if ($termStatuses->contains(null) || $submission->terms->pluck('id')->diff($termStatuses->keys())->isNotEmpty()) {
            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Validation Failed',
                'message' => 'Please select the academic status for every term record.',
            ]);
        }

        DB::transaction(function () use ($submission, $termStatuses) {
            $this->storeApprovedAcademicHistory($submission, $termStatuses);

            $submission->update([
                'status' => 'approved',
                'reviewed_at' => now(),
                'reviewed_by' => Auth::id(),
                'return_reason' => null,
            ]);
        });

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title' => 'Academic history approved',
            'message' => 'The academic history submission was approved.',
        ]);
    }

    private function storeApprovedAcademicHistory(ScholarAcademicHistorySubmission $submission, $termStatuses): void
    {
        foreach ($submission->terms as $historyTerm) {
            $schoolInfo = $submission->scholar?->schoolInfo()
                ->firstOrCreate(
                    [
                        'campus_id' => $historyTerm->campus_id,
                        'campus_course_id' => $historyTerm->campus_course_id,
                        'curriculum_id' => $historyTerm->curriculum_id,
                    ],
                    [
                        'school_year' => $historyTerm->academic_year,
                    ]
                );

            $termRecord = ScholarTerm::updateOrCreate(
                [
                    'scholar_id' => $submission->scholar_id,
                    'scholar_school_id' => $schoolInfo?->id,
                    'term_id' => $historyTerm->term_id,
                    'academic_year' => $historyTerm->academic_year,
                ],
                [
                    'level_id' => $historyTerm->level_id,
                    'verification_status' => 'approved',
                    'verified_by' => Auth::id(),
                    'rejection_reason' => null,
                ]
            );

            foreach ($historyTerm->subjects as $historySubject) {
                if (! $historySubject->matched_subject_id) {
                    continue;
                }

                ScholarSchoolGrades::updateOrCreate(
                    [
                        'term_record_id' => $termRecord->id,
                        'subject_id' => $historySubject->matched_subject_id,
                    ],
                    [
                        'grade_id' => $this->gradeIdForHistorySubject($historyTerm->campus_id, $historySubject->grade),
                        'remarks' => $historySubject->remarks,
                        'is_deleted' => false,
                    ]
                );
            }

            DB::connection('scholars')
                ->table('scholar_processes')
                ->updateOrInsert(
                    ['term_record_id' => $termRecord->id],
                    [
                        'scholar_id' => $submission->scholar_id,
                        'scholarship_status' => $termStatuses->get($historyTerm->id),
                        'submission' => 'APPROVED',
                        'payroll' => 'NOT SUBMITTED',
                        'is_end' => false,
                        'updated_at' => now(),
                        'updated_by' => Auth::user()?->profile?->fullname,
                    ]
                );

        }
    }

    private function gradeIdForHistorySubject(?int $campusId, ?string $grade): ?int
    {
        if (! $grade) {
            return null;
        }

        $query = SchoolCampusGrades::query()
            ->when($campusId, fn ($query) => $query->where('campus_id', $campusId));

        if (ctype_digit($grade)) {
            $gradeRecord = (clone $query)->whereKey((int) $grade)->first();

            if ($gradeRecord) {
                return $gradeRecord->id;
            }
        }

        return $query->where('grade', $grade)->value('id');
    }

}

