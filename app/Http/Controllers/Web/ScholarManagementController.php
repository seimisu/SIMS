<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\References\LocationClass;
use App\Services\Scholar\Management\ScholarActivationService;
use App\Services\Scholar\Management\ScholarGradeRequestService;
use App\Services\Scholar\Management\ScholarLandbankRequestService;
use App\Services\Scholar\Management\ScholarManagementDetailsService;
use App\Services\Scholar\Management\ScholarManagementListService;
use App\Services\Scholar\Management\ScholarManagementOptionsService;
use App\Services\Scholar\Management\ScholarManagementUpdateService;
use App\Services\Scholar\Management\ScholarProfileRequestService;
use App\Services\Scholar\Management\ScholarTransferService;
use App\Support\SystemPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Vinkla\Hashids\Facades\Hashids;

class ScholarManagementController extends Controller
{
    public function index(Request $request, LocationClass $location)
    {
        $permissions = app(SystemPermissions::class);
        $user = Auth::user();

        $options = app(ScholarManagementOptionsService::class)->build($request, $permissions);

        $schoolFilter = $options['schoolFilter'];
        $programFilter = $options['programFilter'];
        $scholarTypeFilter = $options['scholarTypeFilter'];
        $statusFilter = $options['statusFilter'];
        $academicStatusOptions = $options['academicStatusOptions'];
        $monitoringAcademicYear = $options['monitoringAcademicYear'];
        $monitoringTermId = $options['monitoringTermId'];
        $monitoringSubmissionStatus = $options['monitoringSubmissionStatus'];
        $monitoringYearOptions = $options['monitoringYearOptions'];
        $monitoringTermOptions = $options['monitoringTermOptions'];
        $submissionStatusOptions = $options['submissionStatusOptions'];
        $standingOptions = $options['standingOptions'];
        $selectedMonitoringYear = $options['selectedMonitoringYear'];
        $selectedMonitoringTerm = $options['selectedMonitoringTerm'];
        $selectedSubmissionStatus = $options['selectedSubmissionStatus'];
        $statusOptions = $options['statusOptions'];
        $programOptions = $options['programOptions'];
        $subProgramOptions = $options['subProgramOptions'];
        $yearOptions = $options['yearOptions'];
        $transferCourseOptions = $options['transferCourseOptions'];
        $termOptions = $options['termOptions'];
        $subjectOptions = $options['subjectOptions'];
        $gradeOptions = $options['gradeOptions'];
        $schoolOptions = $options['schoolOptions'];
        $courseOptions = $options['courseOptions'];
        $generateSubjects = $options['generateSubjects'];
        $filterSchool = $options['filterSchool'];

        return Inertia::render(
            'Web/scholarsPage',
            [
                'scholars' => app(ScholarManagementListService::class)->paginate(
                    $request,
                    $permissions,
                    $user,
                    [
                        'academicYear' => $monitoringAcademicYear,
                        'termId' => $monitoringTermId,
                        'submissionStatus' => $monitoringSubmissionStatus,
                    ],
                    $academicStatusOptions
                ),
                'details' => $request->input('id')
                    ? fn () => app(ScholarManagementDetailsService::class)->find($request->input('id'), $permissions, $user)
                    : null,
                'resultSearch' => request('findAddress')
                    ? ($location->getFullAddress(request('findAddress'), false) ?? [])
                    : [],
                'schoolFilter' => $schoolFilter,
                'programFilter' => $programFilter,
                'scholarTypeFilter' => $scholarTypeFilter,
                'statusFilter' => $statusFilter,
                'monitoringYearOptions' => $monitoringYearOptions,
                'monitoringTermOptions' => $monitoringTermOptions,
                'submissionStatusOptions' => $submissionStatusOptions,
                'standingOptions' => $standingOptions,
                'selectedMonitoringYear' => $selectedMonitoringYear,
                'selectedMonitoringTerm' => $selectedMonitoringTerm,
                'selectedSubmissionStatus' => $selectedSubmissionStatus,
                'statusOptions' => $statusOptions,
                'programOptions' => $programOptions,
                'subProgramOptions' => $subProgramOptions,
                'yearOptions' => $yearOptions,
                'transferCourseOptions' => $transferCourseOptions,
                'termOptions' => $termOptions,
                'subjectOptions' => $subjectOptions,
                'gradeOptions' => $gradeOptions,
                'schoolOptions' => $schoolOptions,
                'courseOptions' => $courseOptions,
                'generateSubjects' => $generateSubjects,
                'OpenDetail' => $request->input('id') ?? null,
                'filterSearch' => $request->input('search') ?? null,
                'filterSchool' => $filterSchool,
            ]
        );
    }

    public function update(string $id, string $type, Request $request)
    {
        try {
            if (! app(SystemPermissions::class)->can(Auth::user(), 'scholars.update')) {
                abort(403, 'Unauthorized');
            }

            if ($type !== 'personal') {
                return redirect()->back()->with('flash', [
                    'status' => 'error',
                    'title' => 'Save Failed',
                    'message' => 'Invalid scholar update type.',
                ]);
            }

            $data = $request->validate([
                'first_name' => 'nullable|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'suffix' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
                'contact_no' => 'nullable|string|max:20',
                'birth_place' => 'nullable|string|max:255',
                'birth_date' => 'nullable|date',
                'religion' => 'nullable|string|max:255',
                'civil_status' => 'nullable|string|max:255',
                'fulladdress' => 'nullable',
                'address' => 'nullable',
                'program' => 'nullable',
                'sub_program' => 'nullable',
                'award_year' => 'nullable',
                'status' => 'nullable',
                'school' => 'nullable',
                'course' => 'nullable',
                'schoolId' => 'nullable',
                'guardian_name' => 'nullable|string|max:255',
                'guardian_id_no' => 'nullable|string|max:255',
                'guardian_place_issue' => 'nullable|string|max:255',
                'guardian_date_issue' => 'nullable|date',
                'acc_name' => 'nullable|string|max:255',
                'acc_no' => 'nullable|string|max:16',
            ]);

            $decodedId = Hashids::decode($id)[0] ?? 0;
            $flash = app(ScholarManagementUpdateService::class)->updatePersonal($decodedId, $data);

            return redirect()->back()->with('flash', $flash);
        } catch (\Throwable $th) {
            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Save Failed',
                'message' => $th->getMessage(),
            ]);
        }
    }

    public function activation(string $id)
    {
        $decodedId = Hashids::decode($id)[0] ?? 0;
        $flash = app(ScholarActivationService::class)->sendActivationLink($decodedId);

        return redirect()->back()->with('flash', $flash);
    }

    public function profileRequest(string $type, Request $request)
    {
        $permission = $type === 'accept' ? 'profile-requests.approve' : 'profile-requests.reject';
        if (! app(SystemPermissions::class)->can(Auth::user(), $permission)) {
            abort(403, 'Unauthorized');
        }

        $flash = app(ScholarProfileRequestService::class)->decide($type, $request->input('data'));

        return redirect()->back()->with('flash', $flash);
    }

    public function landbankRequest(string $type, Request $request)
    {
        $permission = $type === 'accept' ? 'landbank-requests.approve' : 'landbank-requests.reject';
        if (! app(SystemPermissions::class)->can(Auth::user(), $permission)) {
            abort(403, 'Unauthorized');
        }

        $flash = app(ScholarLandbankRequestService::class)->decide($type, $request->input('data'));

        return redirect()->back()->with('flash', $flash);
    }

    public function gradeRequest(string $type, Request $request)
    {
        $permission = $type === 'accept' ? 'grade-submissions.approve' : 'grade-submissions.reject';
        if (! app(SystemPermissions::class)->can(Auth::user(), $permission)) {
            abort(403, 'Unauthorized');
        }

        $flash = app(ScholarGradeRequestService::class)->decide($type, $request->input('data'));

        return redirect()->back()->with('flash', $flash);
    }

    public function transfer(string $id, string $type, Request $request)
    {
        $decodedId = Hashids::decode($id)[0] ?? 0;
        if ($type == 'school') {
            $data = $request->validate([
                'school' => 'required',
                'course' => 'required',
            ]);

            $flash = app(ScholarTransferService::class)->transferSchool($decodedId, $data);

            return redirect()->back()->with('flash', $flash);
        } else {
            return redirect()->back()->with([
                'flash' => [
                    'status' => 'error',
                    'title' => 'Transfer Failed',
                    'message' => 'Invalid transfer type.',
                ],
            ]);
        }
    }
}
