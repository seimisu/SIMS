<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\SchoolRequest;
use App\Models\CurriculumReplication;
use App\Models\LocationCity;
use App\Models\SchoolCampusCourseCurriculums;
use App\Models\SchoolCampuses;
use App\Models\Schools;
use App\References\ListClass;
use App\References\LocationClass;
use App\Services\Notifications\RoleBellNotificationService;
use App\Support\GradingSystem;
use App\Support\SystemPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            $school = SchoolCampuses::notDeleted()->with([
                'courses.subjects' => fn ($q) => $q->where('is_delete', false),
                'courses' => fn ($q) => $q->where('is_delete', false),
                'courses.specializations' => fn ($q) => $q->where('is_delete', false)->where('is_active', true),
                'grades' => fn ($q) => $q->where('is_delete', false)->orderBy('grade', 'asc'),
                'info' => fn ($q) => $q->select(['id', 'campus_id', 'dean', 'registrar', 'contact', 'email'])->where('is_delete', false),
                'semesters' => fn ($q) => $q->where('is_delete', false),
                'address',
            ])->findOrFail($id);

            // Convert entire object recursively to plain arrays
            $schoolDetail = $school ? json_decode(json_encode($school), true) : null;
        }

        return Inertia::render('Web/schoolPage', [
            // table shool
            'universities' => fn () => $ref->getSchools('table', $request->input('search')),
            // option ref
            'classOption' => fn () => $ref->getRefs('option', null, null, 'Class'),
            'classificationOption' => fn () => $ref->getRefs('option', null, null, 'Term Type'),
            'agencyOption' => fn () => $ref->getAgencies(false),
            'gradingOption' => fn () => collect($ref->getRefs('option', null, null, 'Grading System'))
                ->filter(fn ($option) => in_array($option['name'] ?? null, GradingSystem::SUPPORTED, true))
                ->values(),
            'courseOption' => fn () => $ref->getCourses('option'),
            'subClassOption' => fn () => $ref->getRefs('option', null, 'Subject', null),
            'semesterOption' => fn () => request('semesterType')
                ? $ref->getRefs('option', null, null, request('semesterType'))
                : null,
            // search
            'resultSearch' => request('autosuggest')
                ? ($location->getFullAddress(request('autosuggest')) ?? [])
                : [],
            'schoolDetail' => $schoolDetail,
            'subjectDetail' => request('campusCourseId')
                ? SchoolCampusCourseCurriculums::whereHas('course.campus', fn ($campus) => $campus->notDeleted())->with([
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
                        'specialization_id',
                        'requirement_type',
                        'updated_at',
                        'updated_by',
                        'created_by',
                    )->where('is_delete', false),
                ])
                    ->select([
                        'id',
                        'campus_course_id',
                        'years as yearLevel',
                        'elective_limit',
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
                SchoolCampuses::notDeleted()->when(Auth::check() && $permissions->shouldScopeToRegion($user), function ($query) use ($permissions, $user) {
                    $query->whereHas('address', function ($q) use ($permissions, $user) {
                        $q->where('region_code', $permissions->regionCodeFor($user));
                    });
                })
                    ->where('id', request('id'))
                    ->exists()
                : false,

            'schoolEdit' => request('school_id')
                ? Schools::with(['campuses' => function ($query) use ($permissions, $user) {
                    if ($permissions->shouldScopeToRegion($user)) {
                        $query->whereHas('address', function ($q) use ($permissions, $user) {
                            $q->where('region_code', $permissions->regionCodeFor($user));
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
                        $q->notDeleted()->where('school_id', request('schoolId'));
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

        $school = DB::transaction(function () use ($data) {
            $school = Schools::create([
                'name' => Str::lower($data['name']),
                'shortcut' => $data['abbreviation'],
                'reference_id' => $data['class']['id'],
                'created_by' => Auth::user()->profile->fullname,
            ]);

            foreach ($data['campuses'] as $campus) {
                $slice = explode('-', $campus['address']['id']);
                $municipality = LocationCity::where('code', $slice[1])->value('name');
                $campusModel = $school->campuses()->create([
                    'is_main' => $campus['main'],
                    'term_id' => $campus['semester']['id'],
                    'name' => isset($campus['name']) ? Str::lower($campus['name']) : null,
                    'agency_id' => $campus['agency']['id'],
                    'grading_id' => $campus['grading']['id'],
                    'generated_name' => isset($campus['name']) ? Str::upper($data['name']).'-'.Str::upper($campus['name']) : Str::upper($data['name']).'-'.Str::upper($municipality),
                    'created_by' => Auth::user()->profile->fullname,
                ]);

                $campusModel->address()->create([

                    'address' => $campus['street'] ?? null,
                    'barangay_code' => $slice[0],
                    'municipality_code' => $slice[1],
                    'province_code' => $slice[2],
                    'region_code' => $slice[3],
                    'created_by' => Auth::user()->profile->fullname,
                ]);
            }

            return $school;
        });
        app(RoleBellNotificationService::class)->notifyRegionalAndScholarshipStaff(
            'school_added',
            'New school added',
            "{$school->name} was added to the school list.",
            '/academic/schools',
            'schools',
            $school->id
        );

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

        DB::transaction(function () use ($data, $find, $type) {
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
                    $municipality = LocationCity::where('code', $slice[1])->value('name');
                    if (! empty($campus['id'])) {
                        $campusModel = $find->campuses()->notDeleted()->findOrFail($campus['id']);
                        $campusModel->update([
                            'is_main' => $campus['main'],
                            'term_id' => $campus['semester']['id'],
                            'agency_id' => $campus['agency']['id'],
                            'name' => isset($campus['name']) ? Str::lower($campus['name']) : null,
                            'generated_name' => isset($campus['name']) ? Str::upper($data['name']).'-'.Str::upper($campus['name']) : Str::upper($data['name']).'-'.Str::upper($municipality),
                            'grading_id' => $campus['grading']['id'],
                            'updated_by' => Auth::user()->profile->fullname,
                            'updated_at' => now(),
                        ]);
                        $campusModel->address()->updateOrCreate([], [
                            'address' => $campus['street'] ?? null,
                            'barangay_code' => $slice[0],
                            'municipality_code' => $slice[1],
                            'province_code' => $slice[2],
                            'region_code' => $slice[3],

                            'updated_at' => now(),
                        ]);
                    } else {
                        $newCampus = $find->campuses()->create([
                            'name' => isset($campus['name']) ? Str::lower($campus['name']) : null,
                            'is_main' => $campus['main'],
                            'term_id' => $campus['semester']['id'],
                            'agency_id' => $campus['agency']['id'],
                            'generated_name' => isset($campus['name']) ? Str::upper($data['name']).'-'.Str::upper($campus['name']) : Str::upper($data['name']).'-'.Str::upper($municipality),
                            'grading_id' => $campus['grading']['id'],
                            'created_by' => Auth::user()->profile->fullname,
                        ]);

                        $newCampus->address()->create([
                            'address' => $campus['street'] ?? null,
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
        });
        app(RoleBellNotificationService::class)->notifyRegionalAndScholarshipStaff(
            'school_updated',
            'School updated',
            "{$find->name} was updated in the school list.",
            '/academic/schools',
            'schools',
            $find->id
        );

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title' => 'University Updated',
            'message' => 'University successfully updated.',
        ]);
    }

    public function destroy($id, $type)
    {
        $campus = SchoolCampuses::notDeleted()->find($id);
        if (! $campus) {
            return redirect()->route('academic.universities')->withErrors([
                'campus' => 'This campus is no longer available. Please refresh the school list.',
            ]);
        }

        $permissions = app(SystemPermissions::class);
        $user = Auth::user();
        if ($permissions->shouldScopeToRegion($user)
            && (! $permissions->regionCodeFor($user)
                || ! $campus->address()->where('region_code', $permissions->regionCodeFor($user))->exists())) {
            return redirect()->route('academic.universities')->withErrors([
                'campus' => 'You do not have permission to delete campuses outside your assigned region.',
            ]);
        }

        try {
            DB::transaction(function () use ($campus) {
                $school = Schools::whereKey($campus->school_id)->lockForUpdate()->firstOrFail();
                $campus->update(['is_delete' => true]);

                if (! $school->campuses()->notDeleted()->exists()) {
                    $school->update(['is_delete' => true]);
                }
            });

            return redirect()->route('academic.universities')->with('flash', [
                'status' => 'success',
                'title' => 'School Campus Deleted',
                'message' => 'School campus successfully deleted.',
            ]);
        } catch (\Throwable $e) {

            Log::channel('errortracer')->error('Deleting campus', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('academic.universities', ['id' => $campus->id])->withErrors([
                'campus' => 'Unable to delete this campus. Please try again. If the problem continues, contact an administrator.',
            ]);
        }
    }
}
