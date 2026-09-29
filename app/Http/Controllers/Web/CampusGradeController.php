<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\CampusGradeRequest;
use App\Models\SchoolCampuses;
use App\Models\SchoolCampusGrades;
use App\Services\Academic\CampusGradeRuleValidator;
use Illuminate\Support\Facades\Auth;

class CampusGradeController extends Controller
{
    public function store(CampusGradeRequest $request, CampusGradeRuleValidator $validator)
    {
        $data = $request->validated();
        $campus = SchoolCampuses::with('grading:id,name')->findOrFail($data['campusId']);
        $validator->validate($campus, $data);

        $grade = SchoolCampusGrades::firstOrCreate([
            'campus_id' => $data['campusId'],
            'grade' => $data['grade'],
            'is_delete' => false,
        ], [
            'lower' => $data['lower'],
            'upper' => $data['upper'],
            'is_failed' => $data['fail'],
            'is_drop' => $data['drop'],
            'is_incomplete' => $data['incomplete'],
            'is_withdrawn' => $data['withdrawn'] ?? false,
            'created_by' => Auth::user()->profile->fullname,
        ]);

        return redirect()->back()->with('flash', [
            'status' => $grade->wasRecentlyCreated ? 'success' : 'info',
            'title' => $grade->wasRecentlyCreated ? 'Campus Grade Created' : 'Campus Grade Already Exists',
            'message' => $grade->wasRecentlyCreated ? 'Campus grade successfully created.' : 'This campus grade already exists, so no duplicate was created.',
        ]);
    }

    public function update(CampusGradeRequest $request, string $id, string $type, CampusGradeRuleValidator $validator)
    {
        $data = $request->validated();
        $find = SchoolCampusGrades::findOrFail($id);

        if ($type == 'form') {
            $campus = SchoolCampuses::with('grading:id,name')->findOrFail($find->campus_id);
            $validator->validate($campus, $data, $find->id, (bool) $find->is_active);

            $find->update([
                'grade' => $data['grade'],
                'lower' => $data['lower'],
                'upper' => $data['upper'],
                'is_failed' => $data['fail'],
                'is_drop' => $data['drop'],
                'is_incomplete' => $data['incomplete'],
                'is_withdrawn' => $data['withdrawn'] ?? false,
                'created_by' => Auth::user()->profile->fullname,
            ]);
        } else {
            if ($data['isActive']) {
                $campus = SchoolCampuses::with('grading:id,name')->findOrFail($find->campus_id);
                $validator->validate($campus, [
                    'lower' => $find->lower,
                    'upper' => $find->upper,
                    'fail' => $find->is_failed,
                    'drop' => $find->is_drop,
                    'incomplete' => $find->is_incomplete,
                    'withdrawn' => $find->is_withdrawn,
                ], $find->id);
            }

            $find->update([
                'is_active' => $data['isActive'],
            ]);
        }

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title' => 'Campus Course Updated',
            'message' => 'Campus course successfully updated.',
        ]);
    }

    public function destroy(int $id)
    {

        $find = SchoolCampusGrades::findOrFail($id);
        $find->update([
            'is_delete' => true,
        ]);

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title' => 'Campus Course Deleted',
            'message' => 'Campus course successfully deleted.',
        ]);
    }
}
