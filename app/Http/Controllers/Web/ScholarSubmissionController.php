<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\requestHistory;
use App\Models\ScholarTerm;
use App\Models\Scholars;
use App\Models\StudentDocument;
use App\Models\studentLandbankRequest;
use App\Models\StudentProfileRequest;
use App\Support\SystemPermissions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        $status = $request->input('status', 'pending');
        $search = $request->input('search');

        return Inertia::render('Web/scholarSubmissionsPage', [
            'filters' => [
                'tab' => $tab,
                'status' => $status,
                'search' => $search,
            ],
            'counts' => [
                'grades' => ScholarTerm::where('verification_status', 'submitted')->count(),
                'profile' => StudentProfileRequest::where('status', 'pending')->count(),
                'landbank' => studentLandbankRequest::where('status', 'pending')->count(),
            ],
            'gradeSubmissions' => fn () => $tab === 'grades'
                ? $this->gradeSubmissions($request, $permissions, $user)
                : null,
            'profileRequests' => fn () => $tab === 'profile'
                ? $this->profileRequests($request, $permissions, $user)
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
            'landbankRequest' => fn () => $request->input('scholar') && $request->input('dialog') === 'landbank'
                ? $this->landbankRequest($request, $permissions, $user)
                : null,
        ]);
    }

    private function gradeSubmissions(Request $request, SystemPermissions $permissions, $user)
    {
        $status = $request->input('status', 'pending') === 'all'
            ? null
            : ($request->input('status') === 'pending' ? 'submitted' : $request->input('status'));
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
            ->when($status, fn ($query) => $query->where('verification_status', $status))
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

    private function profileRequests(Request $request, SystemPermissions $permissions, $user)
    {
        return StudentProfileRequest::with('scholar.profile', 'scholar.program:id,name', 'scholar.type:id,name')
            ->when($request->input('status', 'pending') !== 'all', fn ($query) => $query->where('status', $request->input('status', 'pending')))
            ->when($permissions->shouldScopeToRegion($user), function ($query) use ($permissions, $user) {
                $query->whereHas('scholar.schoolInfo.campus.address', fn ($address) => $address->where('region_code', $permissions->regionCodeFor($user)));
            })
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

    private function landbankRequests(Request $request, SystemPermissions $permissions, $user)
    {
        return studentLandbankRequest::with('scholar.profile', 'scholar.program:id,name', 'scholar.type:id,name')
            ->when($request->input('status', 'pending') !== 'all', fn ($query) => $query->where('status', $request->input('status', 'pending')))
            ->when($permissions->shouldScopeToRegion($user), function ($query) use ($permissions, $user) {
                $query->whereHas('scholar.schoolInfo.campus.address', fn ($address) => $address->where('region_code', $permissions->regionCodeFor($user)));
            })
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

        return ScholarTerm::where('scholar_id', $scholar->id)
            ->where('verification_status', 'submitted')
            ->latest('created_at')
            ->get()
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
