<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLogs;
use App\Models\ListCourse;
use App\Models\ListReferences;
use App\Models\SchoolCampuses;
use App\Models\User;
use App\Notifications\CoordinatorUpdateInfoNotification;
use App\Notifications\UpdateSemesterCoordinatorNotification;
use App\References\ListClass;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Vinkla\Hashids\Facades\Hashids;

class SchoolCoordinatorController extends Controller
{
    public function index(Request $request, ListClass $ref)
    {
        $campus_id = Auth::user()->school_id;
        $campus = SchoolCampuses::select('id', 'school_id', 'generated_name', 'grading_id', 'term_id')->find($campus_id);
        $search = $request->input('search');

        return Inertia::render('Web/schoolCoordinatorPage', [
            'campus' => [
                'name' => $campus->generated_name,
                'address' => $campus->address?->full_address['name'],
                'gradeSystem' => $campus->grading->name,

            ],
            'info' => [
                'president' => $campus->info?->dean ?? 'N/A',
                'registrar' => $campus->info?->registrar ?? 'N/A',
                'contact' => $campus->info?->contact ?? 'N/A',
                'email' => $campus->info?->email ?? 'N/A',
            ],
            'subClassOption' => $ref->getRefs('option', null, 'Subject', null),
            'semesterOption' => Inertia::optional(fn () => $ref->getRefs('option', null, null, $campus->term->name)),
            'semesterDate' => $campus->semesters()
                ->where('is_delete', false)
                ->where('is_active', true)
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->get()
                ->map(fn ($semester) => [
                    'name' => $semester->semester->name,
                    'startDate' => Carbon::parse($semester->start_date)->setTimezone('Asia/Manila')->format('M Y'),
                    'endDate' => Carbon::parse($semester->end_date)->setTimezone('Asia/Manila')->format('M Y'),
                    'submissionDate' => Carbon::parse($semester->submission_date)->setTimezone('Asia/Manila')->format('M d, Y'),
                ])->first(),
            'activeDate' => Inertia::optional(
                fn () => $campus->semesters()
                    ->where('is_delete', false)
                    ->where('is_active', true)
                    ->get()
                    ->map(fn ($semester) => [
                        'name' => $semester->semester->name,
                        'startDate' => Carbon::parse($semester->start_date)->setTimezone('Asia/Manila')->format('M Y'),
                        'endDate' => Carbon::parse($semester->end_date)->setTimezone('Asia/Manila')->format('M Y'),
                        'submissionDate' => Carbon::parse($semester->submission_date)->setTimezone('Asia/Manila')->format('M d, Y'),
                    ])
            ),

            'programs' => $campus
                ->courses()
                ->with('course:id,name,abbreviation', 'campus')
                ->where('is_delete', false)
                ->where(function ($q) use ($search) {
                    $q->where('years', 'like', "%{$search}%")
                        ->orWhereHas('course', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('course', function ($q) use ($search) {
                            $q->whereRaw('UPPER(abbreviation) LIKE ?', [
                                '%'.strtoupper($search).'%',
                            ]);
                        });
                })
                ->latest()
                ->paginate(15)
                ->through(fn ($course) => [
                    'id' => Hashids::encode($course->id),
                    'course' => $course->course->name,
                    'abbreviation' => $course->course->abbreviation,
                    'yearLevel' => $course->years,
                    'term_id' => $course->campus->term_id,
                    'curriculumCount' => $course->curriculum()->where('is_delete', false)->count(),
                ]),
            'logs' => AuditLogs::where('user_id', Auth::id())
                ->latest()
                ->limit(15)
                ->get()
                ->map(function ($log) {
                    return [
                        'user' => $log->user->profile->fullname,
                        'action' => $log->action,
                        'old_data' => $log->old_data,
                        'new_data' => $log->new_data,
                        'created_at' => Carbon::parse($log->created_at)->diffForHumans(),
                        'date' => Carbon::parse($log->created_at)->format('F j, Y g:i A'),
                    ];
                }),
            'grades' => Inertia::optional(
                fn () => $campus->grades()
                    ->where('is_delete', false)
                    ->orderBy('grade', 'asc')
                    ->get()
                    ->map(fn ($grade) => [
                        'id' => Hashids::encode($grade->id),
                        'grade' => $grade->grade,
                        'lower' => $grade->lower,
                        'upper' => $grade->upper,
                        'is_failed' => $grade->is_failed,
                        'is_incomplete' => $grade->is_incomplete,
                        'is_drop' => $grade->is_drop,
                        'is_active' => $grade->is_active,
                    ])
            ),
            'programOptions' => Inertia::optional(
                fn () => ListCourse::where('is_delete', false)
                    ->whereDoesntHave('schoolCampuses', fn ($q) => $q->where('campus_id', $campus_id))
                    ->get()
                    ->map(fn ($course) => [
                        'id' => Hashids::encode($course->id),
                        'name' => $course->name,
                        'abbreviation' => $course->abbreviation,
                    ])
            ),
            'subjectDetail' => Inertia::optional(
                fn () => $campus->courses()
                    ->with('course:id,name,abbreviation', 'curriculum')
                    ->where('id', Hashids::decode($request->input('campusCourseId'))[0] ?? null)
                    ->where('is_delete', false)
                    ->get()
                    ->map(fn ($course) => [
                        'id' => $course->id,
                        'course' => $course->course->name,
                        'abbreviation' => $course->course->abbreviation,
                        'years' => $course->years,
                        'curriculum' => $course->curriculum
                            ->where('is_delete', false)
                            ->map(fn ($curriculum) => [
                                'id' => $curriculum->id,
                                'campus_course_id' => $curriculum->campus_course_id,
                                'yearLevel' => $curriculum->years,
                                'semesterTypeId' => $curriculum->semester_type_id,
                                'is_duplicated' => $curriculum->is_duplicated,
                                'subjects' => $curriculum->subjects()->select(
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
                                )->where('is_delete', false)->get(),
                            ]),
                    ])
                    ->first()
            ),
        ]);
    }

    public function updateSemester(Request $request)
    {

        $campus_id = Auth::user()->school_id;
        $campus = SchoolCampuses::findOrFail($campus_id);

        $semester = $request->input('semester', []);

        foreach ($semester as &$semesterData) {
            $semesterData['startDate'] = Carbon::parse($semesterData['startDate'])->setTimezone('Asia/Manila')->format('Y-m-d');
            $semesterData['endDate'] = Carbon::parse($semesterData['endDate'])->setTimezone('Asia/Manila')->endOfMonth()->format('Y-m-d');
            $semesterData['submissionDate'] = Carbon::parse($semesterData['submissionDate'])->setTimezone('Asia/Manila')->format('Y-m-d');
        }

        $request->merge([
            'semester' => $semester,
        ]);

        $validatedData = $request->validate([
            'semester' => ['required', 'array'],
            'semester.*.semester_id' => ['required'],
            'semester.*.startDate' => ['nullable', 'date'],
            'semester.*.endDate' => ['nullable', 'date'],
            'semester.*.submissionDate' => ['nullable', 'date'],
        ]);

        // foreach ($validatedData['semester'] as $semesterData) {

        //     $semester = $campus->semesters()->findOrFail($semesterData['semester_id']);
        //     $oldData = Arr::only($semester->toArray(), ['start_date', 'end_date', 'submission_date']);

        //     $semester->update([
        //         'start_date' => Carbon::parse($semesterData['startDate'])->format('Y-m-d'),
        //         'end_date' => Carbon::parse($semesterData['endDate'])->format('Y-m-d'),
        //         'submission_date' => Carbon::parse($semesterData['submissionDate'])->format('Y-m-d'),
        //     ]);

        //     $newData = Arr::only($semester->toArray(), ['start_date', 'end_date', 'submission_date']);

        //     AuditLogs::create([
        //         'user_id' => Auth::id(),
        //         'old_data' => $oldData,
        //         'new_data' => $newData,
        //         'action' => "Updated semester {$semester->name}",
        //     ]);

        // }
        $semester = $campus->semesters()->update([
            'is_active' => false,
        ]);

        foreach ($validatedData['semester'] as $key => $value) {

            $semester = $campus->semesters()->create([
                'semester_id' => $value['semester_id'],
                'start_date' => $value['startDate'] ?? null,
                'end_date' => $value['endDate'] ?? null,
                'submission_date' => $value['submissionDate'] ?? null,
            ]);

            $newData = Arr::only($semester->toArray(), ['start_date', 'end_date', 'submission_date', ListReferences::find($value['semester_id'])->name]);

            AuditLogs::create([
                'user_id' => Auth::id(),
                'old_data' => null,
                'new_data' => $newData,
                'action' => 'Updated semester '.ListReferences::find($value['semester_id'])->name,
            ]);
        }

        Notification::send(User::whereHas('role', fn ($q) => $q->where('slug', 'regional staff'))->whereHas('profile', fn ($q) => $q->where('agency_id', $campus->agency_id))->get(), new UpdateSemesterCoordinatorNotification(Auth::user()->profile->fullname, $campus->generated_name));

        return redirect()->back()->with([
            'flash' => [
                'status' => 'success',
                'title' => 'Semester Updated',
                'message' => 'The semester dates have been successfully updated.',
            ],
        ]);
    }

    public function createGrade(Request $request)
    {
        $campus_id = Auth::user()->school_id;

        $validated = $request->validate([
            'grade' => ['required', 'string'],
            'lower' => ['nullable', 'integer', 'lte:upper'],
            'upper' => ['nullable', 'integer', 'gte:lower'],
            'drop' => ['nullable', 'boolean'],
            'fail' => ['nullable', 'boolean'],
            'incomplete' => ['nullable', 'boolean'],
        ]);

        $campus = SchoolCampuses::findOrFail($campus_id);

        $grade = $campus->grades()->create([
            'grade' => $validated['grade'],
            'lower' => $validated['lower'] ?? null,
            'upper' => $validated['upper'] ?? null,
            'is_drop' => $validated['drop'] ?? false,
            'is_delete' => false,
            'is_failed' => $validated['fail'] ?? false,
            'is_incomplete' => $validated['incomplete'] ?? false,
        ]);

        AuditLogs::create([
            'user_id' => Auth::id(),
            'old_data' => null,
            'new_data' => [
                'grade' => $grade->grade,
                'range' => $grade->upper || $grade->lower ? $grade->lower.'-'.$grade->upper : 'Not Set',
                'drop' => $grade->drop ? 'Set true' : 'Set false',
                'fail' => $grade->fail ? 'Set true' : 'Set false',
                'incomplete' => $grade->incomplete ? 'Set true' : 'Set false',
            ],
            'action' => 'Created new grade',
        ]);

        return redirect()->back()->with([
            'flash' => [
                'status' => 'success',
                'title' => 'Grade Created',
                'message' => 'The grade has been successfully created.',
            ],
        ]);
    }

    public function deleteGrade(Request $request, string $id)
    {
        $campus_id = Auth::user()->school_id;
        $grade_id = Hashids::decode($id)[0] ?? null;

        $campus = SchoolCampuses::findOrFail($campus_id);
        $grade = $campus->grades()->findOrFail($grade_id);

        $grade->update(['is_delete' => true]);

        AuditLogs::create([
            'user_id' => Auth::id(),
            'old_data' => [
                'grade' => $grade->grade,
                'range' => $grade->upper || $grade->lower ? $grade->lower.'-'.$grade->upper : 'Not Set',
                'drop' => $grade->drop ? 'Set true' : 'Set false',
                'fail' => $grade->fail ? 'Set true' : 'Set false',
                'incomplete' => $grade->incomplete ? 'Set true' : 'Set false',
            ],
            'new_data' => null,
            'action' => 'Deleted grade',
        ]);

        return redirect()->back()->with([
            'flash' => [
                'status' => 'success',
                'title' => 'Grade Deleted',
                'message' => 'The grade has been successfully deleted.',
            ],
        ]);
    }

    public function createProgram(Request $request)
    {
        $campus_id = Auth::user()->school_id;
        $course = $request->input('course');

        $course['id'] = Hashids::decode($course['id'])[0] ?? null; // or Hashids::decode(...)

        $request->merge([
            'course' => $course,
        ]);
        $validatedData = $request->validate([
            'course' => ['required', 'array'],
            'course.id' => ['exists:list_courses,id'],

            'years' => ['required', 'integer'],
        ]);

        $course = ListCourse::find($validatedData['course']['id']);
        $campus = SchoolCampuses::find($campus_id);

        if ($campus->courses()->where('course_id', $course->id)->exists()) {
            return redirect()->back()->with([
                'flash' => [
                    'status' => 'error',
                    'title' => 'Program Already Exists',
                    'message' => 'The selected program already exists in your campus.',
                ],
            ]);
        }

        $campus->courses()->create([
            'course_id' => $course->id,
            'years' => $validatedData['years'],
            'created_by' => Auth::user()->profile->fullname,
        ]);

        AuditLogs::create([
            'user_id' => Auth::id(),
            'old_data' => null,
            'new_data' => [
                'course' => $course->name,
                'abbreviation' => $course->abbreviation,
                'years' => $validatedData['years'],
            ],
            'action' => 'Created new program',
        ]);

        return redirect()->back()->with([
            'flash' => [
                'status' => 'success',
                'title' => 'Program Created',
                'message' => 'The program has been successfully created.',
            ],
        ]);
    }

    public function updateInfo(Request $request)
    {
        $campus_id = Auth::user()->school_id;
        $campus = SchoolCampuses::find($campus_id);

        $validatedData = $request->validate([
            'president' => 'required|string|max:255',
            'registrar' => 'required|string|max:255',
            'contact' => 'required|string|max:20',
            'email' => 'required|email|max:255',
        ]);

        $info = $campus->info()->updateOrCreate(
            ['campus_id' => $campus_id],
            [
                'dean' => $validatedData['president'],
                'registrar' => $validatedData['registrar'],
                'contact' => $validatedData['contact'],
                'email' => $validatedData['email'],
                'updated_by' => Auth::id(),
            ]
        );

        if ($info->wasChanged()) {
            $oldData = Arr::except($info->getPrevious(), [
                'updated_at',
                'created_at',
            ]);

            $newData = Arr::except($info->getChanges(), [
                'updated_at',
                'created_at',
            ]);

            AuditLogs::create([
                'user_id' => Auth::id(),
                'old_data' => $oldData,
                'new_data' => $newData,
                'action' => 'Updated school information',
            ]);

            Notification::send(User::whereHas('role', fn ($q) => $q->where('slug', 'regional staff'))->whereHas('profile', fn ($q) => $q->where('agency_id', $campus->agency_id))->get(), new CoordinatorUpdateInfoNotification(Auth::user()->profile->fullname, 'updateInfoSchool'));

            return redirect()->back()->with([
                'flash' => [
                    'status' => 'success',
                    'title' => 'School Updated',
                    'message' => 'School information successfully updated.',
                ],
            ]);
        }

        return redirect()->back()->with([
            'flash' => [
                'status' => 'info',
                'title' => 'No Changes Made',
                'message' => 'No changes were made to the school information.',
            ],
        ]);
    }
}
