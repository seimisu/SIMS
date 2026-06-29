<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\SchoolRequest;
use App\Models\CurriculumReplication;
use App\Models\SchoolCampusCourseCurriculums;
use App\Models\SchoolCampuses;
use App\Models\Schools;
use App\References\ListClass;
use App\References\LocationClass;
use App\Support\SystemPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Vinkla\Hashids\Facades\Hashids;

class SchoolController extends Controller
{
    public function index(ListClass $ref, Request $request, LocationClass $location)
    {
        $permissions = app(SystemPermissions::class);
        $user = Auth::user();

        $schoolDetail = null;

        if ($id = request('id')) {
            $school = SchoolCampuses::with([
                'courses.subjects' => fn ($q) => $q->where('is_delete', false),
                'courses' => fn ($q) => $q->where('is_delete', false),
                'grades' => fn ($q) => $q->where('is_delete', false)->orderBy('grade', 'asc'),
                'info' => fn ($q) => $q->select(['id', 'campus_id', 'dean', 'registrar', 'contact', 'email'])->where('is_delete', false),
                'semesters' => fn ($q) => $q->where('is_delete', false),
                'address',
            ])->find($id);

            // Convert entire object recursively to plain arrays
            $schoolDetail = $school ? json_decode(json_encode($school), true) : null;
        }

        return Inertia::render('Web/schoolPage', [
            // table shool
            'universities' => $ref->getSchools('table', $request->input('search')),
            // option ref
            'classOption' => $ref->getRefs('option', null, null, 'Class'),
            'classificationOption' => $ref->getRefs('option', null, null, 'Term Type'),
            'agencyOption' => $ref->getAgencies(false),
            'gradingOption' => $ref->getRefs('option', null, null, 'Grading System'),
            'courseOption' => $ref->getCourses('option'),
            'subClassOption' => $ref->getRefs('option', null, 'Subject', null),
            'semesterOption' => request('semesterType')
                ? $ref->getRefs('option', null, null, request('semesterType'))
                : null,
            // search
            'resultSearch' => request('autosuggest')
                ? ($location->getFullAddress(request('autosuggest')) ?? [])
                : [],
            'schoolDetail' => $schoolDetail,
            'subjectDetail' => request('campusCourseId')
                ? SchoolCampusCourseCurriculums::with([
                    'subjects' => fn ($q) => $q->select(
                        'id',
                        'curriculum_id',
                        'semester_id',
                        'curriculum_id as curriculumId',
                        'name',
                        'subject_code as subjectCode',
                        'year',
                        'unit',
                        'subject_class',
                        'updated_at',
                        'updated_by',
                        'created_by',
                    )->where('is_delete', false),

                ])
                    ->select([
                        'id',
                        'campus_course_id',
                        'years as yearLevel',
                        'semester_type_id as semesterTypeId',
                        'is_duplicated',
                    ])
                    ->withExists([
                        'replication as has_replication' => function ($q) {
                            $q->where('user_id', Auth::id())
                                ->whereNull('deleted_at');
                        },
                    ])
                    ->where('campus_course_id', request('campusCourseId'))
                    ->where('semester_type_id', request('semTypeId'))
                    ->where('is_delete', false)
                    ->orderBy('id', 'desc')
                    ->get()
                    ->toArray()
                : null,
            'regionAccess' => request('id') ?
                SchoolCampuses::when(Auth::check() && $permissions->isRegionalStaff($user), function ($query) use ($user) {
                    $query->where('agency_id', Auth::user()->profile->agency_array['id']);
                })
                    ->where('id', request('id'))
                    ->exists()
                : false,

            'schoolEdit' => request('school_id')
                ? Schools::with(['campuses' => function ($query) use ($permissions, $user) {
                    if (! $permissions->isAdministrator($user)) {
                        $query->whereHas('agency', function ($q) use ($user) {
                            $q->where('id', $user->profile?->agency_id);
                        });
                    }
                    $query->with('address');
                    $query->where('is_delete', false);
                }])
                    ->where('id', request('school_id'))
                    ->first()?->toArray()
                : null,
            'templateOptions' => request('campusCourseId')
                ? CurriculumReplication::select('id', 'curriculum_id')
                    ->with([
                        'curriculum' => fn ($q) => $q
                            ->select('id', 'campus_course_id', 'years', 'semester_type_id')
                            ->with([
                                'course' => fn ($q) => $q
                                    ->select('id', 'campus_id', 'course_id')
                                    ->with([
                                        'campus',
                                    ]),
                            ]),
                    ])
                    ->where('user_id', Auth::id())
                    ->whereHas('curriculum.course.campus', function ($q) {
                        $q->where('school_id', request('schoolId'));
                    })
                    ->get()
                    ->map(fn ($q) => [
                        'curriculum_id' => Hashids::encode($q->curriculum_id),
                        'name' => $q->curriculum->course->course['name'].' - Curriculum '.$q->curriculum->years,
                    ])
                : null,
        ]);
    }

    public function store(SchoolRequest $request)
    {
        $data = $request->validated();

        $school = Schools::create([
            'name' => Str::lower($data['name']),
            'shortcut' => $data['abbreviation'],
            'reference_id' => $data['class']['id'],
            'created_by' => Auth::user()->profile->fullname,
        ]);

        foreach ($data['campuses'] as $campus) {
            $slice = explode('-', $campus['address']['id']);
            $sliceName = explode(',', $campus['address']['name']);
            $campusModel = $school->campuses()->create([
                'is_main' => $campus['main'],
                'term_id' => $campus['semester']['id'],
                'name' => isset($campus['name']) ? Str::lower($campus['name']) : null,
                'agency_id' => $campus['agency']['id'],
                'grading_id' => $campus['grading']['id'],
                'generated_name' => isset($campus['name']) ? Str::upper($data['name']).'-'.Str::upper($campus['name']) : Str::upper($data['name']).'-'.Str::of($sliceName[1])->trim()->upper(),
                'created_by' => Auth::user()->profile->fullname,
            ]);

            $campusModel->address()->create([

                'address' => $campus['street'],
                'barangay_code' => $slice[0],
                'municipality_code' => $slice[1],
                'province_code' => $slice[2],
                'region_code' => $slice[3],
                'created_by' => Auth::user()->profile->fullname,
            ]);
        }

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title' => 'University Created',
            'message' => 'University successfully created.',
        ]);
    }

    public function update(SchoolRequest $request, string $id, string $type)
    {

        $data = $request->validated();
        $find = Schools::findOrFail($id);

        if ($type == 'form') {

            $find->update([
                'name' => $data['name'],
                'shortcut' => $data['abbreviation'],
                'reference_id' => $data['class']['id'],
                'updated_by' => Auth::user()->profile->fullname,
                'updated_at' => now(),
            ]);

            foreach ($data['campuses'] as $campus) {
                $slice = explode('-', $campus['address']['id']);
                $sliceName = explode(',', $campus['address']['name']);
                if (! empty($campus['id'])) {
                    $campusModel = $find->campuses()->find($campus['id']);
                    $campusModel->update([
                        'is_main' => $campus['main'],
                        'term_id' => $campus['semester']['id'],
                        'agency_id' => $campus['agency']['id'],
                        'name' => isset($campus['name']) ? Str::lower($campus['name']) : null,
                        'generated_name' => isset($campus['name']) ? Str::upper($data['name']).'-'.Str::upper($campus['name']) : Str::upper($data['name']).'-'.Str::of($sliceName[1])->trim()->upper(),
                        'grading_id' => $campus['grading']['id'],
                        'updated_by' => Auth::user()->profile->fullname,
                        'updated_at' => now(),
                    ]);
                    $campusModel->address()->update([
                        'address' => $campus['street'],
                        'barangay_code' => $slice[0],
                        'municipality_code' => $slice[1],
                        'province_code' => $slice[2],
                        'region_code' => $slice[3],

                        'updated_at' => now(),
                    ]);
                } else {
                    $newCampus = $find->campuses()->create([
                        'is_main' => $campus['main'],
                        'term_id' => $campus['semester']['id'],
                        'agency_id' => $campus['agency']['id'],
                        'generated_name' => isset($campus['name']) ? Str::upper($data['name']).'-'.Str::upper($campus['name']) : Str::upper($data['name']).'-'.Str::of($sliceName[1])->trim()->upper(),
                        'grading_id' => $campus['grading']['id'],
                        'created_by' => Auth::user()->profile->fullname,
                    ]);

                    $newCampus->address()->create([
                        'address' => $campus['street'],
                        'barangay_code' => $slice[0],
                        'municipality_code' => $slice[1],
                        'province_code' => $slice[2],
                        'region_code' => $slice[3],
                        'created_by' => Auth::user()->profile->fullname,
                    ]);
                }
            }
        } else {
            $find->update([
                'is_active' => $data['isActive'],
            ]);
        }

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title' => 'University Updated',
            'message' => 'University successfully updated.',
        ]);
    }

    public function destroy($id, $type)
    {
        try {
            $university = SchoolCampuses::findOrFail($id);
            $university->is_delete = true;
            $university->save();

            $school = Schools::withCount(['campuses' => fn ($q) => $q->where('is_delete', false)])
                ->where('id', $university->school_id)
                ->first();

            if ($school && $school->campuses_count === 0) {
                $school->is_delete = true;
                $school->save();
            }

            return redirect()->back()->with('flash', [
                'status' => 'success',
                'title' => 'School Campus Deleted',
                'message' => 'School campus successfully deleted.',
            ]);
        } catch (\Throwable $e) {

            Log::channel('errortracer')->error('Deleting campus', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Something went wrong',
                'message' => 'Please report this to Administrator.',
            ]);
        }
    }
}
