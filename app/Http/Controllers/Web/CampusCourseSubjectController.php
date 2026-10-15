<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SchoolCampusCourseSubjects;

class CampusCourseSubjectController extends Controller
{
    public function destroy(int $id)
    {

        try {
            $find = SchoolCampusCourseSubjects::whereHas('campusCourse.campus', fn ($campus) => $campus->notDeleted())->findOrFail($id);
        } catch (\Exception $e) {
            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Subject Not Found',
                'message' => 'The subject you are trying to delete does not exist.',
            ]);
        }

        $find = SchoolCampusCourseSubjects::whereHas('campusCourse.campus', fn ($campus) => $campus->notDeleted())->findOrFail($id);
        $find->update([
            'is_delete' => true,
        ]);

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title' => 'Subject Deleted',
            'message' => 'Subject successfully deleted.',
        ]);
    }
}
