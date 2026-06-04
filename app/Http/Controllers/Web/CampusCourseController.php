<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\CampusCourseRequest;
use App\Models\SchoolCampusCourses;
use Illuminate\Support\Facades\Auth;

class CampusCourseController extends Controller
{
    public function store(CampusCourseRequest $request)
    {
        $data = $request->validated();

        $check = SchoolCampusCourses::where('course_id', $data['course']['id'])
            ->where('campus_id', $data['campusId'])
            ->first();
        if ($check) {
            $check->update([
                'is_delete' => false,
                'years' => $data['years'],
            ]);

            return redirect()->back()->with('flash', [
                'status' => 'success',
                'title' => 'Campus Course Recreated',
                'message' => 'Campus course successfully recreated.',
            ]);
        } else {
            SchoolCampusCourses::create([
                'years' => $data['years'],
                'course_id' => $data['course']['id'],
                'campus_id' => $data['campusId'],
                'created_by' => Auth::user()->profile->fullname,
            ]);

            return redirect()->back()->with('flash', [
                'status' => 'success',
                'title' => 'Campus Course Created',
                'message' => 'Campus course successfully created.',
            ]);
        }

    }

    public function update(CampusCourseRequest $request, string $id, string $type)
    {
        $data = $request->validated();
        $find = SchoolCampusCourses::findOrFail($id);

        if ($type == 'form') {

            $find->update([
                'course_id' => $data['course']['id'],
                'years' => $data['years'],
            ]);

            // foreach ($data['subjects'] as $subject) {

            //     if (!empty($subject['id'])) {
            //         $campuseCourseModel = $find->subjects()->find($subject['id']);
            //         $campuseCourseModel->update([
            //             'name'          => $subject['name'],
            //             'subject_code'  => $subject['code'],
            //             'subject_class' => $subject['class']['name'],
            //             'unit'          => $subject['unit'],
            //             'updated_by'    => Auth::user()->profile->fullname,
            //         ]);
            //     } else {
            //         $find->subjects()->create([
            //             'name'          => $subject['name'],
            //             'subject_code'  => $subject['code'],
            //             'subject_class' => $subject['class']['name'],
            //             'unit'          => $subject['unit'],
            //             'created_by'    => Auth::user()->profile->fullname,
            //         ]);
            //     }
            // }
        } else {
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

        $find = SchoolCampusCourses::findOrFail($id);
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
