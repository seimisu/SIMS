<?php

namespace App\Services\Scholar\Submission;

use App\Models\requestHistory;
use App\Models\ListStatuses;
use App\Models\LocationBarangays;
use App\Models\LocationCity;
use App\Models\LocationProvinces;
use App\Models\LocationRegions;
use App\Models\ScholarAcademicHistorySubmission;
use App\Models\ScholarTerm;
use App\Models\SchoolCampusGrades;
use App\Models\Scholars;
use App\Models\StudentDocument;
use App\Models\studentLandbankRequest;
use App\Models\StudentProfileRequest;
use App\Services\Academic\AcademicPerformanceEvaluationService;
use App\Support\SystemPermissions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Vinkla\Hashids\Facades\Hashids;

class ScholarSubmissionPageService
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
        $options = ListStatuses::whereIn('type', ['standing', 'ongoing'])
            ->where('is_active', true)
            ->where('is_delete', false)
            ->orderBy('id')
            ->get()
            ->map(fn ($status) => [
                'id' => Str::upper($status->name),
                'name' => Str::upper($status->name),
            ])
            ->values();

        return $options
            ->concat([
                ['id' => 'TERMINATED WITH SERVICE OBLIGATION', 'name' => 'TERMINATED WITH SERVICE OBLIGATION'],
                ['id' => 'CONTINUED TO SUBMIT GRADES', 'name' => 'CONTINUED TO SUBMIT GRADES'],
            ])
            ->unique(fn ($status) => Str::upper($status['name']))
            ->values();
    }

    private function profileRequests(Request $request, SystemPermissions $permissions, $user)
    {
        $regionalScholarIds = $this->regionalScholarIds($permissions, $user);

        return StudentProfileRequest::with('scholar.profile', 'scholar.program:id,name', 'scholar.type:id,name')
            ->where('status', 'pending')
            ->when($regionalScholarIds !== null, fn ($query) => $query->whereIn('scholar_id', $regionalScholarIds))
            ->when($request->input('search'), function ($query, $search) {
                $query->whereHas('scholar', function ($scholar) use ($search) {
                    $scholar->where('spas_no', 'ILIKE', '%'.$search.'%')
                        ->orWhereHas('profile', function ($profile) use ($search) {
                            $profile->whereRaw("CONCAT(lname, ' ', fname, ' ', COALESCE(mname, '')) ILIKE ?", ['%'.$search.'%']);
                        });
                });
            })
            ->orderBy('created_at')
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
                    'is_failed' => $subject->is_failed,
                    'is_incomplete' => $subject->is_incomplete,
                    'is_dropped' => $subject->is_dropped,
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
        $regionalScholarIds = $this->regionalScholarIds($permissions, $user);

        return studentLandbankRequest::with('scholar.profile', 'scholar.program:id,name', 'scholar.type:id,name')
            ->where('status', 'pending')
            ->when($regionalScholarIds !== null, fn ($query) => $query->whereIn('scholar_id', $regionalScholarIds))
            ->when($request->input('search'), function ($query, $search) {
                $query->whereHas('scholar', function ($scholar) use ($search) {
                    $scholar->where('spas_no', 'ILIKE', '%'.$search.'%')
                        ->orWhereHas('profile', function ($profile) use ($search) {
                            $profile->whereRaw("CONCAT(lname, ' ', fname, ' ', COALESCE(mname, '')) ILIKE ?", ['%'.$search.'%']);
                        });
                });
            })
            ->orderBy('created_at')
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
            ->map(function ($term) use ($currentTerm, $previousTerm) {
                $recommendation = null;

                if ($term->id === $currentTerm?->id) {
                    $recommendation = $previousTerm
                        ? app(AcademicPerformanceEvaluationService::class)->evaluate($previousTerm)
                        : [
                            'recommended_status' => 'GOOD STANDING',
                            'recommended_status_normalized' => 'GOOD STANDING',
                            'policy_group' => 'First Submission',
                            'manual_review' => false,
                            'reasons' => [
                                'No previous graded term is available for evaluation yet.',
                                'First grade submission is recommended as Good Standing by default.',
                            ],
                            'metrics' => [
                                'curriculum_year' => null,
                                'course_years' => null,
                                'failed_count' => 0,
                                'incomplete_count' => 0,
                                'dropped_units' => 0,
                                'has_previous_deficiency' => false,
                                'term' => null,
                            ],
                        ];
                }

                $subjects = $term->subjects->map(function ($subject) {
                    $gradeValue = is_numeric($subject->grade?->grade) ? (float) $subject->grade->grade : null;
                    $unit = is_numeric($subject->subject?->unit) ? (float) $subject->subject->unit : null;
                    $isAcademic = Str::lower($subject->subject?->subject_class ?? '') === 'academic';
                    $isCounted = $isAcademic
                        && $gradeValue !== null
                        && $unit !== null
                        && ! ($subject->grade?->is_drop || $subject->grade?->is_incomplete);

                    return [
                        'subject' => $subject->subject?->name,
                        'class' => $subject->subject?->subject_class,
                        'code' => $subject->subject?->subject_code,
                        'unit' => $subject->subject?->unit,
                        'grade' => $subject->grade,
                        'is_drop' => (bool) $subject->grade?->is_drop,
                        'is_failed' => (bool) $subject->grade?->is_failed,
                        'is_incomplete' => (bool) $subject->grade?->is_incomplete,
                        'total' => $isCounted ? round($gradeValue * $unit, 2) : null,
                        'is_counted' => $isCounted,
                    ];
                });
                $countedSubjects = $subjects->where('is_counted', true);
                $totalUnits = $countedSubjects->sum(fn ($subject) => (float) ($subject['unit'] ?? 0));
                $totalGradePoints = $countedSubjects->sum(fn ($subject) => (float) ($subject['total'] ?? 0));

                return [
                    'id' => $term->id,
                    'term' => $term->term?->name,
                    'termType' => $term->termType?->name,
                    'subjects' => $subjects,
                    'summary' => [
                        'units' => $totalUnits,
                        'total' => round($totalGradePoints, 2),
                        'average' => $totalUnits > 0 ? number_format($totalGradePoints / $totalUnits, 2, '.', '') : null,
                    ],
                    'totalUnit' => $totalUnits,
                    'school' => $term->schoolInfo?->campus?->generated_name,
                    'course' => $term->schoolInfo?->course?->course?->name,
                    'academicYear' => $term->academic_year,
                    'status' => $term->verification_status,
                    'remarks' => null,
                    'scholarshipStatus' => null,
                    'scholarshipRecommendation' => $recommendation,
                    'scholarshipEvaluationTerm' => $recommendation && $previousTerm ? [
                        'id' => $previousTerm->id,
                        'academicYear' => $previousTerm->academic_year,
                        'term' => $previousTerm->term?->name,
                    ] : null,
                    'files' => StudentDocument::where('term_record_id', $term->id)->get(),
                ];
            });
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
            $barangay = $this->locationName(LocationBarangays::class, $item->barangay);
            $municipality = $this->locationName(LocationCity::class, $item->municipality);
            $province = $this->locationName(LocationProvinces::class, $item->province);
            $region = $this->locationName(LocationRegions::class, $item->region, 'region');

            return [
                'count' => $requestNo,
                'request_id' => $item->id,
                'purpose' => $item->purpose,
                'first_name' => $item->first_name,
                'middle_name' => $item->middle_name,
                'last_name' => $item->last_name,
                'suffix' => $item->suffix,
                'address' => $item->address,
                'barangay' => $barangay,
                'municipality' => $municipality,
                'province' => $province,
                'region' => $region,
                'civil_status' => $item->civil_status,
                'contact_no' => $item->contact_no,
                'email' => $item->email,
                'fullAddress' => implode(', ', array_filter([$item->address, $barangay, $municipality, $province, $region])),
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
                'firstNameStored' => $scholar->profile?->fname,
                'middleNameStored' => $scholar->profile?->mname,
                'lastNameStored' => $scholar->profile?->lname,
                'suffixStored' => $scholar->profile?->suffix,
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

    private function locationName(string $model, ?string $value, string $displayColumn = 'name'): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return $model::where('code', $value)->value($displayColumn) ?? $value;
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
                'hasNameStored' => filled($scholar->landbank?->account_name),
                'hasNoStored' => filled($scholar->landbank?->account_number),
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

    private function regionalScholarIds(SystemPermissions $permissions, $user)
    {
        if (! $permissions->shouldScopeToRegion($user)) {
            return null;
        }

        return Scholars::whereHas('schoolInfo.campus.address', fn ($address) => $address->where('region_code', $permissions->regionCodeFor($user)))
            ->pluck('id')
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
