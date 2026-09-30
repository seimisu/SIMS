<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\SchoolCampusSemesterRequest;
use App\Models\SchoolCampuses;
use App\Models\SchoolCampusSemesters;
use App\Services\Academic\CampusAcademicPeriodService;
use App\Support\SystemPermissions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class SchoolCampusSemesterController extends Controller
{
    public function store(
        SchoolCampusSemesterRequest $request,
        CampusAcademicPeriodService $periods,
        SystemPermissions $permissions
    )
    {
        $data = $request->validated();
        $campus = SchoolCampuses::with('address')->findOrFail($data['campusId']);
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
    )
    {
        abort_unless($type === 'status', 404);
        $data = $request->validate(['status' => ['required', 'in:open,closed']]);
        $period = SchoolCampusSemesters::with('campus.address')->findOrFail($id);
        $this->authorizeCampus($period->campus, $permissions);
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
