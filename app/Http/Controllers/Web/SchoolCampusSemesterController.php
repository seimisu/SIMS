<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\SchoolCampusSemesterRequest;
use App\Models\SchoolCampuses;
use App\Models\SchoolCampusSemesters;
use App\Services\Academic\CampusAcademicPeriodService;
use App\Support\SystemPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SchoolCampusSemesterController extends Controller
{
    public function store(
        SchoolCampusSemesterRequest $request,
        CampusAcademicPeriodService $periods,
        SystemPermissions $permissions
    ) {
        $data = $request->validated();
        $campus = SchoolCampuses::notDeleted()->with('address')->findOrFail($data['campusId']);
        $this->authorizeCampus($campus, $permissions);
        $periods->create($campus, $data, Auth::id());

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title' => $data['status'] === 'open' ? 'Academic Period Opened' : 'Academic Period Saved',
            'message' => 'The campus academic period was created successfully.',
        ]);
    }

    public function update(
        Request $request,
        $id,
        $type,
        CampusAcademicPeriodService $periods,
        SystemPermissions $permissions
    ) {
        abort_unless(in_array($type, ['status', 'deadline', 'edit'], true), 404);
        $period = SchoolCampusSemesters::whereHas('campus', fn ($campus) => $campus->notDeleted())->with('campus.address')->findOrFail($id);
        $this->authorizeCampus($period->campus, $permissions);
        if ($type === 'edit') {
            $data = $request->validate([
                'submissionDate' => ['required', 'date_format:Y-m-d'],
                'status' => ['required', Rule::in($period->status === 'draft' ? ['draft', 'open', 'closed'] : ['open', 'closed'])],
            ]);
            DB::transaction(function () use ($period, $periods, $data) {
                $period->update(['submission_date' => $data['submissionDate']]);
                if ($period->status !== $data['status']) {
                    $periods->changeStatus($period, $data['status'], Auth::id());
                }
            });

            return redirect()->back()->with('flash', [
                'status' => 'success',
                'title' => 'Academic Period Updated',
                'message' => 'The submission deadline and period status were saved successfully.',
            ]);
        }
        $data = $type === 'deadline'
            ? $request->validate(['submissionDate' => ['required', 'date_format:Y-m-d']])
            : $request->validate(['status' => ['required', 'in:open,closed']]);
        if ($type === 'deadline') {
            $period->update(['submission_date' => $data['submissionDate']]);

            return redirect()->back()->with('flash', [
                'status' => 'success',
                'title' => 'Submission Deadline Updated',
                'message' => 'The academic period submission deadline was updated successfully.',
            ]);
        }
        $periods->changeStatus($period, $data['status'], Auth::id());

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title' => $data['status'] === 'open' ? 'Academic Period Opened' : 'Academic Period Closed',
            'message' => 'The academic period status was updated successfully.',
        ]);
    }

    private function authorizeCampus(SchoolCampuses $campus, SystemPermissions $permissions): void
    {
        $user = Auth::user();
        if (! $permissions->shouldScopeToRegion($user)) {
            return;
        }

        abort_unless(
            (string) $campus->address?->region_code === (string) $permissions->regionCodeFor($user),
            403
        );
    }
}
