<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\requestHistory;
use App\Models\ListStatuses;
use App\Models\ScholarAcademicHistorySubmission;
use App\Models\ScholarSchoolGrades;
use App\Models\ScholarTerm;
use App\Models\SchoolCampusGrades;
use App\Models\Scholars;
use App\Models\StudentDocument;
use App\Models\studentLandbankRequest;
use App\Models\StudentProfileRequest;
use App\Support\SystemPermissions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Vinkla\Hashids\Facades\Hashids;

class ScholarSubmissionController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $permissions = app(SystemPermissions::class);
        $tab = $request->input('tab', 'grades');
        $status = in_array($tab, ['grades', 'history'], true) ? 'submitted' : 'pending';
        $search = $request->input('search');

        return Inertia::render('Web/scholarSubmissionsPage', [
            'filters' => [
                'tab' => $tab,
                'status' => $status,
                'search' => $search,
            ],
            'counts' => [
                'grades' => ScholarTerm::where('verification_status', 'submitted')->count(),
                'history' => ScholarAcademicHistorySubmission::where('status', 'submitted')->count(),
                'profile' => StudentProfileRequest::where('status', 'pending')->count(),
                'landbank' => studentLandbankRequest::where('status', 'pending')->count(),
            ],
            'standingOptions' => fn () => $this->standingOptions(),
            'gradeSubmissions' => fn () => $tab === 'grades'
                ? $this->gradeSubmissions($request, $permissions, $user)
                : null,
            'profileRequests' => fn () => $tab === 'profile'
                ? $this->profileRequests($request, $permissions, $user)
                : null,
            'academicHistorySubmissions' => fn () => $tab === 'history'
                ? $this->academicHistorySubmissions($request, $permissions, $user)
                : null,
            'landbankRequests' => fn () => $tab === 'landbank'
                ? $this->landbankRequests($request, $permissions, $user)
                : null,
            'details' => fn () => $request->input('scholar')
                ? $this->scholarDetails($request, $permissions, $user)
                : null,
            'subjectRequest' => fn () => $request->input('scholar') && $request->input('dialog') === 'grades'
                ? $this->subjectRequest($request, $permissions, $user)
                : null,
            'personalRequest' => fn () => $request->input('scholar') && $request->input('dialog') === 'profile'
                ? $this->personalRequest($request, $permissions, $user)
                : null,
            'academicHistoryRequest' => fn () => $request->input('submission') && $request->input('dialog') === 'history'
                ? $this->academicHistoryRequest($request, $permissions, $user)
                : null,
            'landbankRequest' => fn () => $request->input('scholar') && $request->input('dialog') === 'landbank'
                ? $this->landbankRequest($request, $permissions, $user)
                : null,
        ]);
    }

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
                        'spas_no' => $submission->scholar?->spas_no ?? $submission->spas_no,
                        'scholarship_status' => $termStatuses->get($historyTerm->id),
                        'submission' => 'APPROVED',
                        'payroll' => 'NOT SUBMITTED',
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

    private function gradeSubmissions(Request $request, SystemPermissions $permissions, $user)
    {
        $search = $request->input('search');

        return ScholarTerm::query()
            ->with([
                'scholar.profile:id,scholar_id,fname,lname,mname,suffix',
                'scholar.program:id,name',
                'scholar.type:id,name',
                'schoolInfo.campus:id,generated_name,agency_id',
                'schoolInfo.campus.agency:id,name',
                'schoolInfo.course.course:id,name',
                'term:id,name',
            ])
            ->where('verification_status', 'submitted')
            ->when($permissions->shouldScopeToRegion($user), function ($query) use ($permissions, $user) {
                $query->whereHas('schoolInfo.campus.address', fn ($address) => $address->where('region_code', $permissions->regionCodeFor($user)));
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->whereHas('scholar.profile', function ($profile) use ($search) {
                        $profile->whereRaw("CONCAT(lname, ' ', fname, ' ', COALESCE(mname, '')) ILIKE ?", ['%'.$search.'%']);
                    })->orWhereHas('scholar', fn ($scholar) => $scholar->where('spas_no', 'ILIKE', '%'.$search.'%'));
                });
            })
            ->orderBy('created_at')
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($term) => [
                'id' => $term->id,
                'scholar_id' => Hashids::encode($term->scholar_id),
                'spas_no' => $term->scholar?->spas_no,
                'fullname' => $this->fullname($term->scholar),
                'program' => $term->scholar?->program?->name,
                'type' => $term->scholar?->type?->name,
                'school' => $term->schoolInfo?->campus?->generated_name,
                'course' => $term->schoolInfo?->course?->course?->name,
                'region' => $term->schoolInfo?->campus?->agency?->name,
                'academic_year' => $term->academic_year,
                'term' => $term->term?->name,
                'status' => $term->verification_status,
                'submitted_at' => $term->created_at?->format('M d, Y h:i A'),
            ]);
    }

    private function standingOptions()
    {
        return ListStatuses::where('type', 'standing')
            ->where('is_active', true)
            ->where('is_delete', false)
            ->orderBy('id')
            ->get()
            ->map(fn ($status) => [
                'id' => Str::upper($status->name),
                'name' => Str::upper($status->name),
            ])
            ->values();
    }

    private function profileRequests(Request $request, SystemPermissions $permissions, $user)
    {
        $regionalSpas = $this->regionalScholarSpas($permissions, $user);

        return StudentProfileRequest::with('scholar.profile', 'scholar.program:id,name', 'scholar.type:id,name')
            ->where('status', 'pending')
            ->when($regionalSpas !== null, fn ($query) => $query->whereIn('spas_no', $regionalSpas))
            ->when($request->input('search'), function ($query, $search) {
                $query->where('spas_no', 'ILIKE', '%'.$search.'%');
            })
            ->orderBy('requested_at')
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($item) => [
                'id' => $item->id,
                'scholar_id' => Hashids::encode($item->scholar?->id),
                'spas_no' => $item->spas_no,
                'fullname' => $this->fullname($item->scholar),
                'program' => $item->scholar?->program?->name,
                'type' => $item->scholar?->type?->name,
                'purpose' => $item->purpose,
                'status' => $item->status,
                'submitted_at' => $item->requested_at ? Carbon::parse($item->requested_at)->format('M d, Y h:i A') : null,
            ]);
    }

    private function academicHistorySubmissions(Request $request, SystemPermissions $permissions, $user)
    {
        return ScholarAcademicHistorySubmission::with([
            'scholar.profile:id,scholar_id,fname,lname,mname,suffix',
            'scholar.program:id,name',
            'scholar.type:id,name',
        ])
            ->withCount(['terms', 'files'])
            ->where('status', 'submitted')
            ->when($permissions->shouldScopeToRegion($user), function ($query) use ($permissions, $user) {
                $query->whereHas('scholar.schoolInfo.campus.address', fn ($address) => $address->where('region_code', $permissions->regionCodeFor($user)));
            })
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('spas_no', 'ILIKE', '%'.$search.'%')
                    ->orWhereHas('scholar.profile', function ($profile) use ($search) {
                        $profile->whereRaw("CONCAT(lname, ' ', fname, ' ', COALESCE(mname, '')) ILIKE ?", ['%'.$search.'%']);
                    });
                });
            })
            ->orderBy('submitted_at')
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($item) => [
                'id' => Hashids::encode($item->id),
                'submission_id' => Hashids::encode($item->id),
                'scholar_id' => Hashids::encode($item->scholar_id),
                'spas_no' => $item->spas_no,
                'fullname' => $this->fullname($item->scholar),
                'program' => $item->scholar?->program?->name,
                'type' => $item->scholar?->type?->name,
                'status' => $item->status,
                'terms_count' => $item->terms_count,
                'files_count' => $item->files_count,
                'submitted_at' => $item->submitted_at?->format('M d, Y h:i A'),
            ]);
    }

    private function academicHistoryRequest(Request $request, SystemPermissions $permissions, $user): ?array
    {
        $id = Hashids::decode($request->input('submission'))[0] ?? 0;

        $submission = ScholarAcademicHistorySubmission::with([
            'scholar.profile',
            'scholar.program:id,name',
            'scholar.type:id,name',
            'terms.subjects.matchedSubject',
            'terms.campus:id,generated_name',
            'terms.course.course:id,name',
            'terms.curriculum:id,years',
            'files',
        ])
            ->whereKey($id)
            ->when($permissions->shouldScopeToRegion($user), function ($query) use ($permissions, $user) {
                $query->whereHas('scholar.schoolInfo.campus.address', fn ($address) => $address->where('region_code', $permissions->regionCodeFor($user)));
            })
            ->first();

        if (! $submission) {
            return null;
        }

        $gradeLabels = SchoolCampusGrades::whereIn(
            'id',
            $submission->terms
                ->flatMap(fn ($term) => $term->subjects)
                ->pluck('grade')
                ->filter(fn ($grade) => is_string($grade) && ctype_digit($grade))
                ->unique()
                ->values()
        )->pluck('grade', 'id');

        return [
            'id' => Hashids::encode($submission->id),
            'spas_no' => $submission->spas_no,
            'fullname' => $this->fullname($submission->scholar),
            'program' => $submission->scholar?->program?->name,
            'scholarshipProgram' => $submission->scholar?->type?->name,
            'status' => $submission->status,
            'submitted_at' => $submission->submitted_at?->format('F d, Y h:i A'),
            'reviewed_at' => $submission->reviewed_at?->format('F d, Y h:i A'),
            'return_reason' => $submission->return_reason,
            'terms' => $submission->terms->map(fn ($term) => [
                'id' => $term->id,
                'academic_year' => $term->academic_year,
                'term' => $term->term?->name ?? $term->term_name,
                'level' => $term->level?->name ?? $term->level_name,
                'school' => $term->campus?->generated_name ?? $term->school_name,
                'course' => $term->course?->course?->name ?? $term->course_name,
                'curriculum' => $term->curriculum ? 'Curriculum '.$term->curriculum->years : null,
                'remarks' => $term->remarks,
                'subjects' => $term->subjects->map(fn ($subject) => [
                    'name' => $subject->matchedSubject?->name ?? $subject->subject_name,
                    'code' => $subject->matchedSubject?->subject_code ?? $subject->subject_code,
                    'class' => $subject->matchedSubject?->subject_class ?? $subject->subject_class,
                    'unit' => $subject->unit,
                    'grade' => $gradeLabels->get($subject->grade, $subject->grade),
                    'remarks' => $subject->remarks,
                ]),
            ]),
            'files' => $submission->files->map(fn ($file) => [
                'name' => $file->file_name,
                'path' => $file->file_path,
                'type' => $file->file_type,
            ]),
        ];
    }

    private function landbankRequests(Request $request, SystemPermissions $permissions, $user)
    {
        $regionalSpas = $this->regionalScholarSpas($permissions, $user);

        return studentLandbankRequest::with('scholar.profile', 'scholar.program:id,name', 'scholar.type:id,name')
            ->where('status', 'pending')
            ->when($regionalSpas !== null, fn ($query) => $query->whereIn('spas_no', $regionalSpas))
            ->when($request->input('search'), fn ($query, $search) => $query->where('spas_no', 'ILIKE', '%'.$search.'%'))
            ->orderBy('requested_at')
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($item) => [
                'id' => $item->id,
                'scholar_id' => Hashids::encode($item->scholar?->id),
                'spas_no' => $item->spas_no,
                'fullname' => $this->fullname($item->scholar),
                'program' => $item->scholar?->program?->name,
                'type' => $item->scholar?->type?->name,
                'status' => $item->status,
                'submitted_at' => $item->requested_at ? Carbon::parse($item->requested_at)->format('M d, Y h:i A') : null,
            ]);
    }

    private function scholarDetails(Request $request, SystemPermissions $permissions, $user): ?array
    {
        $scholar = $this->scopedScholar($request, $permissions, $user);

        if (! $scholar) {
            return null;
        }

        return [
            'id' => Hashids::encode($scholar->id),
            'spas_no' => $scholar->spas_no,
            'fullname' => $this->fullname($scholar),
            'type' => ['name' => $scholar->type?->name],
            'program' => ['name' => $scholar->program?->name],
        ];
    }

    private function subjectRequest(Request $request, SystemPermissions $permissions, $user)
    {
        $scholar = $this->scopedScholar($request, $permissions, $user);

        if (! $scholar) {
            return collect();
        }

        $submittedTerms = ScholarTerm::where('scholar_id', $scholar->id)
            ->where('verification_status', 'submitted')
            ->latest('created_at')
            ->get();

        $selectedTermId = (int) $request->input('term');
        $currentTerm = $selectedTermId
            ? $submittedTerms->firstWhere('id', $selectedTermId)
            : $submittedTerms->first();

        if ($currentTerm) {
            $submittedTerms = collect([$currentTerm]);
        }

        $previousTerm = $currentTerm
            ? ScholarTerm::where('scholar_id', $scholar->id)
                ->whereKeyNot($currentTerm->id)
                ->latest('created_at')
                ->latest('id')
                ->first()
            : null;

        return $submittedTerms
            ->when(
                $previousTerm && ! $submittedTerms->contains('id', $previousTerm->id),
                fn ($terms) => $terms->push($previousTerm)
            )
            ->map(fn ($term) => [
                'id' => $term->id,
                'term' => $term->term?->name,
                'termType' => $term->termType?->name,
                'subjects' => $term->subjects->map(fn ($subject) => [
                    'subject' => $subject->subject?->name,
                    'class' => $subject->subject?->subject_class,
                    'code' => $subject->subject?->subject_code,
                    'unit' => $subject->subject?->unit,
                    'grade' => $subject->grade,
                ]),
                'totalUnit' => $term->subjects->sum(fn ($subject) => (int) ($subject->subject?->unit ?? 0)),
                'school' => $term->schoolInfo?->campus?->generated_name,
                'course' => $term->schoolInfo?->course?->course?->name,
                'academicYear' => $term->academic_year,
                'status' => $term->verification_status,
                'remarks' => null,
                'scholarshipStatus' => null,
                'files' => StudentDocument::where('term', $term->id)->get(),
            ]);
    }

    private function personalRequest(Request $request, SystemPermissions $permissions, $user)
    {
        $scholar = $this->scopedScholar($request, $permissions, $user);

        if (! $scholar) {
            return collect();
        }

        $requests = $scholar->profileRequest()->latest('id')->get();

        return $requests->values()->map(function ($item, $index) use ($requests, $scholar) {
            $requestedAt = Carbon::parse($item->requested_at);
            $requestNo = $requestedAt->format('Ymd').'-'.str_pad($requests->count() - $index, 3, '0', STR_PAD_LEFT);

            return [
                'count' => $requestNo,
                'request_id' => $item->id,
                'purpose' => $item->purpose,
                'address' => $item->address,
                'barangay' => $item->barangay,
                'municipality' => $item->municipality,
                'province' => $item->province,
                'region' => $item->region,
                'civil_status' => $item->civil_status,
                'contact_no' => $item->contact_no,
                'email' => $item->email,
                'fullAddress' => implode(', ', array_filter([$item->address, $item->barangay, $item->municipality, $item->province, $item->region])),
                'fullAddressStored' => $scholar->address?->full_address_with_street,
                'file_type' => $item->proof_type,
                'remarks' => $item->remarks,
                'requested_at' => $requestedAt->diffForHumans(),
                'request_date' => $requestedAt->format('F d, Y h:i A'),
                'reviewed_at' => $item->reviewed_at ? Carbon::parse($item->reviewed_at)->diffForHumans() : null,
                'reviewed_by' => $item->reviewed_by,
                'status' => $item->status,
                'file' => $item->proof,
                'emailStored' => $scholar->profile?->email,
                'contactStored' => $scholar->profile?->contact_no,
                'civilStored' => $scholar->profile?->civil_status,
                'spas_no' => $scholar->spas_no,
                'fullname' => $this->fullname($scholar),
                'program' => $scholar->program?->name,
                'scholarshipProgram' => $scholar->type?->name,
                'records' => requestHistory::where('request_type', 'profile')->where('request_no', $requestNo)->first(),
            ];
        });
    }

    private function landbankRequest(Request $request, SystemPermissions $permissions, $user)
    {
        $scholar = $this->scopedScholar($request, $permissions, $user);

        if (! $scholar) {
            return collect();
        }

        $requests = $scholar->landbankRequest()->orderByDesc('id')->get();

        return $requests->values()->map(function ($item, $index) use ($requests, $scholar) {
            $requestNo = Carbon::parse($item->requested_at)->format('Ymd').'-'.str_pad($requests->count() - $index, 3, '0', STR_PAD_LEFT);

            return [
                'count' => $requestNo,
                'request_id' => $item->id,
                'spas_no' => $item->spas_no,
                'fullname' => $this->fullname($scholar),
                'program' => $scholar->program?->name,
                'scholarshipProgram' => $scholar->type?->name,
                'requested_at' => Carbon::parse($item->requested_at)->diffForHumans(),
                'reviewed_at' => $item->reviewed_at ? Carbon::parse($item->reviewed_at)->diffForHumans() : null,
                'request_date' => Carbon::parse($item->requested_at)->format('F d, Y h:i A'),
                'reviewed_by' => $item->reviewed_by,
                'nameStored' => $scholar->landbank?->account_name,
                'noStored' => $scholar->landbank?->account_number,
                'status' => $item->status,
                'name' => $item->acc_name,
                'reject' => $item->rejection_reason,
                'no' => $item->acc_no,
                'file' => $item->uploaded_file,
                'remarks' => $item->request_purpose,
                'type' => $item->uploaded_type,
                'records' => requestHistory::where('request_no', $requestNo)->where('request_type', 'landbank')->first(),
            ];
        });
    }

    private function scopedScholar(Request $request, SystemPermissions $permissions, $user): ?Scholars
    {
        $id = Hashids::decode($request->input('scholar'))[0] ?? 0;

        return Scholars::with(['profile', 'address', 'program:id,name', 'type:id,name', 'landbank'])
            ->whereKey($id)
            ->when($permissions->shouldScopeToRegion($user), function ($query) use ($permissions, $user) {
                $query->whereHas('schoolInfo.campus.address', fn ($address) => $address->where('region_code', $permissions->regionCodeFor($user)));
            })
            ->first();
    }

    private function regionalScholarSpas(SystemPermissions $permissions, $user)
    {
        if (! $permissions->shouldScopeToRegion($user)) {
            return null;
        }

        return Scholars::whereHas('schoolInfo.campus.address', fn ($address) => $address->where('region_code', $permissions->regionCodeFor($user)))
            ->pluck('spas_no')
            ->filter()
            ->values();
    }

    private function fullname(?Scholars $scholar): ?string
    {
        if (! $scholar) {
            return null;
        }

        return trim(collect([
            $scholar->profile?->lname ? $scholar->profile->lname.',' : null,
            $scholar->profile?->fname,
            $scholar->profile?->mname,
            $scholar->profile?->suffix,
        ])->filter()->implode(' '));
    }
}
