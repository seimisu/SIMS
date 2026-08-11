<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\CourseRequest;
use App\Models\ListCourse;
use App\References\ListClass;
use App\Services\RoleBellNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CourseController extends Controller
{
    public function index(ListClass $ref, Request $request)
    {
        return Inertia::render('Web/coursePage', [
            'courses' => $ref->getCourses('main', $request->input('search')),
            'categories' => $ref->getCourses('suggestion')
        ]);
    }

    public function store(CourseRequest $request)
    {
        $data = $request->validated();

        $course = ListCourse::create([
            'name' => $data['name'],
            'field' => is_array($data['field']) ? $data['field']['name'] : $data['field'],
            'abbreviation' => $data['abbreviation'],
            'created_by'    => Auth::user()->profile->fullname
        ]);
        app(RoleBellNotificationService::class)->notifyRegionalAndScholarshipStaff(
            'course_added',
            'New course added',
            "{$course->name} was added to the course list.",
            '/academic/courses',
            'list_courses',
            $course->id
        );

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title'  => 'Course Created',
            'message' => 'Course successfully created.',
        ]);;
    }
    function update(CourseRequest $request, string $id, string $type)
    {

        $data = $request->validated();
        $find = ListCourse::findOrFail($id);

        if ($type == 'form') {
            $find->update([
                'name' => $data['name'],
                'field' => is_array($data['field']) ? $data['field']['name'] : $data['field'],
                'abbreviation' => $data['abbreviation'],
                'updated_by'    => Auth::user()->profile->fullname,
                'updated_at'    => now()
            ]);
            app(RoleBellNotificationService::class)->notifyRegionalAndScholarshipStaff(
                'course_updated',
                'Course updated',
                "{$find->name} was updated in the course list.",
                '/academic/courses',
                'list_courses',
                $find->id
            );
        } else {
            $find->update([
                'is_active' => $data['isActive'],
            ]);
        }

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title'  => 'Course Updated',
            'message' => 'Course successfully updated.',
        ]);
    }

    function destroy(
        int $id
    ) {
        $find = ListCourse::findOrFail($id);
        $find->update([
            'is_delete' => true,
        ]);

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title'  => 'Course Deleted',
            'message' => 'Course successfully deleted.',
        ]);
    }
}
