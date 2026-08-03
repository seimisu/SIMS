<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLogs;
use App\Models\ListCourse;
use App\Models\SchoolCampuses;
use App\Models\User;
use App\Notifications\CoordinatorUpdateInfoNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Vinkla\Hashids\Facades\Hashids;

class SchoolCoordinatorController extends Controller
{
    public function index(Request $request)
    {
        $campus_id = Auth::user()->school_id;
        $campus = SchoolCampuses::select('id', 'school_id', 'generated_name')->find($campus_id);
        $search = $request->input('search');

        return Inertia::render('Web/schoolCoordinatorPage', [
            'campus' => [
                'name' => $campus->generated_name,
                'address' => $campus->address?->full_address['name'],
            ],
            'info' => [
                'president' => $campus->info?->dean ?? 'N/A',
                'registrar' => $campus->info?->registrar ?? 'N/A',
                'contact' => $campus->info?->contact ?? 'N/A',
                'email' => $campus->info?->email ?? 'N/A',
            ],
            'programs' => $campus->courses()
                ->with('course:id,name,abbreviation')
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
            'grades' => Inertia::optional(fn () => $campus->grades()
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
            'programOptions' => Inertia::optional(fn () => ListCourse::where('is_delete', false)
                ->whereDoesntHave('schoolCampuses', fn ($q) => $q->where('campus_id', $campus_id))
                ->get()
                ->map(fn ($course) => [
                    'id' => Hashids::encode($course->id),
                    'name' => $course->name,
                    'abbreviation' => $course->abbreviation,
                ])
            ),
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
