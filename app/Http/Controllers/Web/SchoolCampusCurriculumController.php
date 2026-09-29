<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\SchoolCampusCurriculumRequest;
use App\Models\SchoolCampusCourseCurriculums;
use App\Models\SchoolCampusCourseCurriculumSubjects;
use App\Models\SchoolCampusCourseSpecialization;
use App\Models\SchoolCampuses;
use App\Models\User;
use App\Notifications\UpdateCurriculumNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Vinkla\Hashids\Facades\Hashids;

class SchoolCampusCurriculumController extends Controller
{
    public function store(SchoolCampusCurriculumRequest $request)
    {

        $user = Auth::user();

        $data = $request->validated();

        foreach ($data['multi'] as $curKey => $curriculum) {
            $curriculumGet = SchoolCampusCourseCurriculums::updateOrCreate(
                ['id' => $curriculum['id']],
                [
                    'campus_course_id' => $curriculum['campus_course_id'],
                    'years' => trim($curriculum['yearLevel']),
                    'elective_limit' => $curriculum['elective_limit'] ?? null,
                    'created_by' => Auth::user()->profile->fullname,
                    'semester_type_id' => $curriculum['semesterTypeId'],
                ]
            );

            foreach ($data['multi'][$curKey]['subjects'] as $subKey => $subject) {
                $specializationIds = collect($subject['specialization_options'] ?? [])->pluck('id')->all();
                if ($specializationIds) {
                    $validSpecializationCount = SchoolCampusCourseSpecialization::whereIn('id', $specializationIds)
                        ->where('campus_course_id', $curriculum['campus_course_id'])
                        ->where('is_active', true)
                        ->where('is_delete', false)
                        ->where('starts_at_year', '<=', $subject['year'])
                        ->count();

                    if ($validSpecializationCount !== count($specializationIds)) {
                        throw ValidationException::withMessages([
                            "multi.{$curKey}.subjects.{$subKey}.specialization_options" => 'One or more specializations are not available for this program and year.',
                        ]);
                    }
                }

                $model = SchoolCampusCourseCurriculumSubjects::updateOrCreate(
                    ['id' => $subject['id']],
                    [
                        'curriculum_id' => $curriculumGet->id,
                        'semester_id' => $subject['semester_array']['id'],
                        'year' => $subject['year'],
                        'name' => Str::lower($subject['name']),
                        'subject_code' => $subject['subjectCode'],
                        'subject_class' => $subject['class_array']['name'],
                        'specialization_id' => null,
                        'requirement_type' => $subject['requirement_option']['id'],
                        'unit' => $subject['unit'],
                        'updated_by' => Auth::user()->profile->fullname,
                    ]
                );
                $model->specializations()->sync($specializationIds);

                if ($model->wasRecentlyCreated) {
                    $model->created_by = Auth::user()->profile->fullname;
                    $model->save();
                }
            }
        }

        if ($user->role->name == 'School Coordinator') {
            $campus = SchoolCampuses::find($user->school_id);
            Notification::send(User::whereHas('role', fn ($q) => $q->where('slug', 'regional staff'))->whereHas('profile', fn ($q) => $q->where('agency_id', $campus->agency_id))->get(), new UpdateCurriculumNotification($user->profile->fullname, $request->program['abbreviation'], $campus->generated_name));
        }

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title' => 'Curriculum Created',
            'message' => 'Curriculum successfully created.',
        ]);
    }

    public function copy(int $id)
    {
        try {
            $curriculum = SchoolCampusCourseCurriculums::findOrFail($id);

            $curriculum->replication()->create([
                'user_id' => Auth::user()->id,
            ]);

            return redirect()->back()->with('flash', [
                'status' => 'success',
                'title' => 'Curriculum Copied',
                'message' => 'Curriculum successfully copied.',
            ]);
        } catch (\Exception $e) {

            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Copy Failed',
                'message' => 'An error occurred while copying the curriculum.',
            ]);
        }
    }

    public function paste(int $id, Request $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->validate([
                'select' => ['required', 'array'],
                'select.curriculum_id' => ['string'],
            ]);

            $decoded = Hashids::decode($data['select']['curriculum_id'])[0] ?? null;

            abort_if(! $decoded, 404);

            $curriculumGet = SchoolCampusCourseCurriculums::with([
                'subjects.specializations:id,name,code',
                'subjects' => fn ($q) => $q->select(
                    'id',
                    'semester_id',
                    'year',
                    'name',
                    'subject_code',
                    'subject_class',
                    'requirement_type',
                    'unit',
                    'curriculum_id'
                )->where('is_delete', false),
            ])
                ->where('id', $decoded)
                ->firstOrFail();

            // Optional: ownership check
            // abort_if($curriculumGet->user_id !== Auth::id(), 403);

            $curriculum = SchoolCampusCourseCurriculums::create([
                'campus_course_id' => $id,
                'years' => $curriculumGet->years,
                'elective_limit' => $curriculumGet->elective_limit,
                'created_by' => Auth::user()->profile->fullname,
                'semester_type_id' => $curriculumGet->semester_type_id,

                'is_duplicated' => true,
            ]);

            foreach ($curriculumGet->subjects as $subject) {
                $newSubject = $curriculum->subjects()->create([
                    'semester_id' => $subject->semester_id,
                    'year' => $subject->year,
                    'name' => $subject->name,
                    'subject_code' => $subject->subject_code,
                    'subject_class' => $subject->subject_class,
                    'requirement_type' => $subject->requirement_type,
                    'unit' => $subject->unit,
                    'updated_by' => Auth::user()->profile->fullname,
                ]);

                $targetSpecializationIds = collect();
                if ($subject->specializations->isNotEmpty()) {
                    $targetSpecializationIds = SchoolCampusCourseSpecialization::query()
                        ->where('campus_course_id', $id)
                        ->where('is_active', true)
                        ->where('is_delete', false)
                        ->where(function ($query) use ($subject) {
                            $codes = $subject->specializations->pluck('code')->filter();
                            $names = $subject->specializations->pluck('name')->filter();
                            $query->when($codes->isNotEmpty(), fn ($q) => $q->whereIn('code', $codes))
                                ->when($names->isNotEmpty(), fn ($q) => $q->orWhereIn('name', $names));
                        })
                        ->pluck('id');
                }
                $newSubject->specializations()->sync($targetSpecializationIds);
            }

            DB::commit();

            return redirect()->back()->with('flash', [
                'status' => 'success',
                'title' => 'Curriculum Pasted',
                'message' => 'The curriculum has been successfully pasted.',
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Paste Failed',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function destroySubject(int $id)
    {
        $find = SchoolCampusCourseCurriculumSubjects::findOrFail($id);
        $find->update([
            'is_delete' => true,
        ]);

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title' => 'Subject Deleted',
            'message' => 'Subject successfully deleted.',
        ]);
    }

    public function destroyCurriculum(int $id)
    {
        $find = SchoolCampusCourseCurriculums::findOrFail($id);
        $find->update([
            'is_delete' => true,
        ]);

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title' => 'Curriculum Deleted',
            'message' => 'Curriculum successfully deleted.',
        ]);
    }
}
