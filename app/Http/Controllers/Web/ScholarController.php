<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ScholarRequest;
use App\Imports\CheckScholarImport;
use App\Imports\ScholarImport;
use App\Models\ListPrograms;
use App\Models\ListReferences;
use App\Models\ListStatuses;
use App\Models\LocationBarangays;
use App\Models\LocationCity;
use App\Models\LocationProvinces;
use App\Models\LocationRegions;
use App\Models\Scholars;
use App\Models\ScholarSchoolGrades;
use App\Models\ScholarSchoolInfos;
use App\Models\ScholarTerm;
use App\Models\ScholarUploadedFiles;
use App\Models\ScholarUploadTemp;
use App\Models\SchoolCampusCourses;
use App\Models\SchoolCampuses;
use App\Models\StudentSubjectRequest;
use App\Models\User;
use App\Notifications\ScholarUploadedNotification;
use App\Notifications\ValidatedFilesNotification;
use App\References\LocationClass;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Vinkla\Hashids\Facades\Hashids;

use function Symfony\Component\Clock\now;

class ScholarController extends Controller
{
    public function index(Request $request, LocationClass $location)
    {

        // $scholars = Scholars::select('id', 'spas_no', 'status_id', 'program_id', 'type_id', 'award_year', 'verified_by', 'verified_at', 'validate_status');

        // $scholarDetails = request('id') ? Scholars::select('id', 'spas_no', 'status_id', 'program_id', 'type_id', 'award_year', 'verified_by', 'verified_at', 'validate_status')
        //     ->with([
        //         'status:id,name,icon,color_id',
        //         'status.color:id,background_color,text_color',
        //         'program:id,name',
        //         'type:id,name',
        //         'profile:scholar_id,photo,sex,fname,lname,mname,suffix,email,contact_no,birthplace,birthdate,civil_status,religion',
        //         'schoolInfo' => fn ($q) => $q
        //             ->select('id', 'scholar_id', 'campus_id', 'campus_course_id')
        //             ->with([
        //                 'campus:id,generated_name,agency_id',
        //                 'campus.agency:id,name,slug',
        //                 'campus.address:campus_id,region_code',
        //                 'course' => fn ($q) => $q
        //                     ->select('id', 'course_id')
        //                     ->with([
        //                         'course:id,name',
        //                     ]),
        //             ])
        //             ->latest()
        //             ->limit(1),
        //     ])
        //     ->where('id', Hashids::decode(request('id'))[0] ?? 0)
        //     ->first() : null;

        // return Inertia::render('Web/scholarPage', [
        //     'scholar' => $scholars
        //         ->with([
        //             'status:id,name,icon,color_id',
        //             'status.color:id,background_color,text_color',
        //             'program:id,name',
        //             'type:id,name',
        //             'profile:id,scholar_id,photo,sex,fname,lname,mname,suffix,email,contact_no',
        //             'termRecords' => fn ($q) => $q
        //                 ->select('id', 'scholar_id', 'term_id', 'level_id', 'academic_year', 'scholar_school_id', 'term_type_id')
        //                 ->with([
        //                     'requests' => fn ($q) => $q
        //                         ->select('id', 'term_record_id', 'subject_id', 'reviewed_at', 'reviewed_by', 'status', 'remarks')
        //                         ->with([
        //                             'subject:id,name,year,subject_code,unit,subject_class,semester_id',
        //                         ])
        //                         ->where('status', 'pending'),
        //                 ]),
        //             'schoolInfo' => fn ($q) => $q
        //                 ->select('id', 'scholar_id', 'campus_id', 'campus_course_id')
        //                 ->with([
        //                     'campus:id,generated_name,agency_id',
        //                     'campus.agency:id,name,slug',
        //                     'campus.address:campus_id,region_code',
        //                     'course' => fn ($q) => $q
        //                         ->select('id', 'course_id')
        //                         ->with([
        //                             'course:id,name',
        //                         ]),
        //                 ])
        //                 ->latest()
        //                 ->limit(1),
        //         ])
        //         ->when(request('search'), fn ($q) => $q->whereHas(
        //             'profile',
        //             fn ($q) => $q->whereRaw("CONCAT(lname, ' ', fname, ' ', COALESCE(mname, '')) ILIKE ?", ['%'.request('search').'%'])
        //         ))
        //         ->paginate(10)
        //         ->through(fn ($q) => [
        //             'id' => Hashids::encode($q->id),
        //             'spas_no' => $q->spas_no,
        //             'subjectRequest_cnt' => $q->termRecords
        //                 ->pluck('requests')
        //                 ->flatten()
        //                 ->count(),
        //             'photo' => $q->profile?->photo,
        //             'email' => $q->profile?->email,
        //             'contact_no' => $q->profile?->contact_no,
        //             'sex' => $q->profile?->sex,
        //             'fullname' => trim(collect([
        //                 $q->profile?->lname.',',
        //                 $q->profile?->fname,
        //                 $q->profile?->mname,
        //                 $q->profile?->suffix,
        //             ])->filter()->implode(' ')),
        //             'type' => $q->type?->name,
        //             'program' => $q->program?->name,
        //             'status' => [
        //                 'name' => $q->status?->name,
        //                 'bcolor' => $q->status?->color?->background_color,
        //                 'tcolor' => $q->status?->color?->text_color,
        //                 'icon' => $q->status?->icon,
        //             ],
        //             'course' => $q->schoolInfo?->first()?->course?->course?->name,
        //             'school' => $q->schoolInfo?->first()?->campus?->generated_name,
        //             'awardyear' => $q->award_year,
        //             'agency' => $q->schoolInfo?->first()?->campus?->agency?->slug,
        //             'region' => $q->schoolInfo?->first()?->campus?->address?->region_array,
        //             'verified_by' => $q->verified_by,
        //             'verified_at' => $q->verified_at,
        //             'validate_status' => $q->validate_status,
        //         ]),
        //     'scholarDetails' => request('id') ?
        //         [
        //             'id' => Hashids::encode($scholarDetails?->id),
        //             'spas_no' => $scholarDetails?->spas_no,
        //             'photo' => $scholarDetails?->profile?->photo,
        //             'sex' => $scholarDetails?->profile?->sex,
        //             'fname' => $scholarDetails?->profile?->fname,
        //             'mname' => $scholarDetails?->profile?->mname,
        //             'lname' => $scholarDetails?->profile?->lname,
        //             'suffix' => $scholarDetails?->profile?->suffix,
        //             'email' => $scholarDetails?->profile?->email,
        //             'contact_no' => $scholarDetails?->profile?->contact_no,
        //             'birthplace' => $scholarDetails?->profile?->birthplace,
        //             'birthdate' => Carbon::parse($scholarDetails?->profile?->birthdate)->format('Y-m-d'),
        //             'religion' => $scholarDetails?->profile?->religion,
        //             'civil_status' => $scholarDetails?->profile?->civil_status,

        //         ] : null,
        //     'academic' => request('id') ?
        //         ScholarSchoolInfos::select(
        //             'id',
        //             'campus_id',
        //             'campus_course_id',
        //             'school_year'
        //         )
        //             ->with([
        //                 'campus' => fn ($q) => $q->select('id', 'generated_name', 'term_id')
        //                     ->with(['term:id,name', 'grades']),

        //                 'course' => fn ($q) => $q
        //                     ->select('id', 'course_id')
        //                     ->with([
        //                         'curriculum' => fn ($q) => $q
        //                             ->with([
        //                                 'subjects:id,curriculum_id,name,year,subject_code,unit,subject_class,semester_id',
        //                             ])
        //                             ->where('is_delete', false)
        //                             ->orderBy('id', 'desc'),
        //                     ]),

        //                 'termRecords' => fn ($q) => $q->select('id', 'scholar_school_id', 'term_id', 'level_id', 'term_type_id')
        //                     ->with([
        //                         'term:id,name',
        //                         'level:id,name,others',
        //                         'subjects' => fn ($q) => $q
        //                             ->select('id', 'term_record_id', 'subject_id', 'grade_id')
        //                             ->with([
        //                                 'subject:id,name,year,subject_code,unit,subject_class,semester_id',
        //                                 'grade' => fn ($q) => $q
        //                                     ->select('id', 'grade as name', 'is_incomplete', 'is_failed', 'is_drop', 'is_active'),

        //                             ])->where('is_deleted', false),
        //                         'termType:id,name',

        //                     ]),
        //             ])
        //             ->where('scholar_id', Hashids::decode(request('id'))[0] ?? 0)
        //             ->get()
        //             ->map(function ($q) {

        //                 $records = $q->termRecords
        //                     ->groupBy(fn ($r) => $r->level->name)
        //                     ->map(function ($terms, $levelName) use ($q) {

        //                         return [
        //                             'label' => $levelName,
        //                             'items' => $terms->map(function ($term, $termIndex) use ($q) {
        //                                 return [
        //                                     'term_id' => Hashids::encode($term->id),
        //                                     'index' => $termIndex,
        //                                     'year_level' => $term->level->name,
        //                                     'label' => $term->termType->name,
        //                                     'grades' => optional($term->subjects)->isNotEmpty()
        //                                         ? $term->subjects
        //                                         : $q->course?->curriculum?->first()?->subjects->where('semester_id', $term->term_type_id)->where('year', $term->level?->others),
        //                                 ];
        //                             })->values(),
        //                         ];
        //                     })->values();

        //                 return [
        //                     'name' => $q->campus->generated_name,
        //                     'course' => $q->course?->course?->name,
        //                     'sy' => $q->school_year,
        //                     'term' => $q->campus?->term_array,
        //                     'records' => $records,
        //                     'optionSubject' => $q->course?->curriculum?->first()?->subjects,
        //                     'optionGrades' => $q->campus?->grades->map(fn ($grade) => [
        //                         'id' => $grade->id,
        //                         'name' => $grade->grade,
        //                         'is_incomplete' => $grade->is_incomplete,
        //                         'is_failed' => $grade->is_failed,
        //                         'is_drop' => $grade->is_drop,
        //                         'is_active' => $grade->is_active,

        //                     ]),

        //                 ];
        //             })
        //         : null,
        //     'files' => request('OpenFiles')
        //         ? ScholarUploadedFiles::when(
        //             Auth::check() && Auth::user()->role_array['name'] == 'regional staff',
        //             function ($query) {
        //                 $query->where('region_office', Auth::user()->profile->agency_array['name']);
        //             }
        //         )->When(request('status'), function ($q) {
        //             $q->where('status', Str::lower(request('status')));
        //         })
        //             ->latest()
        //             ->paginate(4)
        //         : null,
        //     'statuses' => Scholars::selectRaw('status_id, count(*) as total')
        //         ->with(['status:id,icon,color_id,name'])
        //         ->groupBy('status_id')
        //         ->get()
        //         ->map(fn ($q) => [
        //             'status' => Str::ucwords($q->status->name),
        //             'icon' => $q->status->icon,
        //             'color_array' => $q->status->color_array,
        //             'total' => $q->total,
        //         ]),
        //     'georesult' => request('geosearch') ? ($location->getFullAddress(request('geosearch'), false) ?? [])
        //         : [],
        //     'subjectRequest' => request('id') ? ScholarTerm::select('id', 'term_id', 'level_id', 'academic_year', 'scholar_school_id', 'term_type_id')
        //         ->with([
        //             'term:id,name',
        //             'level:id,name,others',
        //             'termType:id,name',
        //             'requests' => fn ($q) => $q->select('id', 'term_record_id', 'subject_id', 'reviewed_at', 'reviewed_by', 'status', 'remarks')
        //                 ->with([
        //                     'subject:id,name,year,subject_code,unit,subject_class,semester_id',
        //                 ])
        //                 ->where('status', 'pending'),
        //         ])
        //         ->where('scholar_id', Hashids::decode(request('id'))[0] ?? 0)
        //         ->get()
        //         : null,


        return Inertia::render('Web/reviewPage', [
            'files' => ScholarUploadedFiles::whereNot('status', 'reject')->withCount([
                'temp',
                'temp as active_temp_count' => fn($q) => $q->where('verified_at', '!=', null),
            ])->orderBy('id', 'desc')->paginate(10),
            'selected' => $request->input('id') ? ScholarUploadTemp::where('file_id', Hashids::decode($request->input('id'))[0] ?? 0)->orderBy('id', 'ASC')
                ->get()->map(function ($scholar) {

                    $schoolChange = $scholar->change_school
                        ? SchoolCampuses::where([
                            'is_delete' => false,
                            'is_active' => true,
                            'generated_name' => $scholar->change_school,
                        ])
                        ->first()->only('id', 'generated_name')
                        : null;

                    $courseChange = $scholar->change_course
                        ? SchoolCampusCourses::with(['course', 'campus'])->where([
                            'is_delete' => false,
                            'is_active' => true,
                        ])->whereHas(
                            'course',
                            fn($q) => $q->whereRaw(
                                'LOWER(name) LIKE ?',
                                ['%' . strtolower($scholar->change_course) . '%']
                            )

                        )
                        ->whereHas('campus', fn($q) => $q->where('generated_name', 'like', '%' . $scholar->change_school . '%'))
                        ->first() : null;

                    $courseOption = SchoolCampusCourses::with(['course', 'campus'])->where([
                        'is_delete' => false,
                        'is_active' => true,
                    ])
                        ->whereHas(
                            'campus',
                            fn($q) => $q->where('generated_name', 'like', '%' . $scholar->change_school . '%')
                        )

                        ->get()
                        ->map(function ($course) {
                            return [
                                'id' => $course->course?->id,
                                'name' => Str::upper($course->course?->name),
                                'campus' => $course->campus?->generated_name,
                            ];
                        });

                    return [
                        'id' => $scholar->id,
                        'spas_no' => $scholar->spas_no,
                        'fullname' => $scholar->fullname,
                        'school' => $scholar->school,
                        'course' => $scholar->course,
                        'address' => $scholar->address,
                        'barangay' => $scholar->barangay,
                        'municipality' => $scholar->municipality,
                        'region' => $scholar->region,
                        'province' => $scholar->province,
                        'inputSchool' => $scholar->change_school ? [
                            'id' => $schoolChange['id'] ?? null,
                            'name' => $schoolChange['generated_name'] ?? null,
                        ] : null,
                        'inputCourse' => $scholar->change_course ? [
                            'id' => $courseChange['course']['id'] ?? null,
                            'name' => Str::upper($courseChange['course']['name'] ?? null),
                            'campus' => $courseChange['campus']['generated_name'] ?? null,
                        ] : null,
                        'inputAddress' => $scholar->change_fulladdress,
                        'courseOption' => $courseOption ?? [],
                        'status' => $scholar->status,
                        'standing' => $scholar->standing,
                        'loading' => false,
                        'error1' => null,
                        'error2' => null,
                        'error3' => null,
                        'verified_at' => $scholar->verified_at ? Carbon::parse($scholar->verified_at)->diffForHumans() : null,
                        'verified_by' => $scholar->verified_by,
                    ];
                }) : [],
            'validationStatus' => $request->input('id') ?  ScholarUploadedFiles::whereNot('status', 'reject')->withCount([
                'temp',
                'temp as active_temp_count' => fn($q) => $q->where('verified_at', '!=', null),
            ])->where('id', Hashids::decode($request->input('id'))[0] ?? 0)->get()
                ->map(fn($item) => [
                    'completed' => $item->active_temp_count ?? 0,
                    'total' => $item->temp_count ?? 0,
                ])
                ->first() : [],
            'resultSearch' => request('findAddress')
                ? ($location->getFullAddress(request('findAddress')) ?? [])
                : [],
            'schoolOption' => $request->input('id') ? SchoolCampuses::where([
                'is_delete' => false,
                'is_active' => true,
            ])->get()->map(function ($campus) {
                return [
                    'id' => $campus->id,
                    'name' => $campus->generated_name,
                ];
            }) : [],
            'courseOption' => Inertia::optional(fn() => SchoolCampusCourses::with(['course', 'campus'])->where([
                'is_delete' => false,
                'is_active' => true,
            ])
                ->whereHas(
                    'campus',
                    fn($q) => $q->where('generated_name', 'like', '%' . $request->campus . '%')
                )
                ->get()
                ->map(function ($course) {
                    return [
                        'id' => $course->id,
                        'name' => Str::upper($course->course?->name),
                        'campus' => $course->campus?->generated_name,
                    ];
                })),
        ]);
    }

    public function validate(string $id, Request $request)
    {
        $data = $request->validate(
            [
                'inputAddress' => ['required', 'array'],
                'inputSchool' => ['required', 'array'],
                'inputCourse' => ['required', 'array'],
            ],
            [
                'inputAddress.required' => 'Please select an address.',
                'inputAddress.array' => 'Invalid address format.',

                'inputSchool.required' => 'Please select a school.',
                'inputSchool.array' => 'Invalid school format.',

                'inputCourse.required' => 'Please select a course.',
                'inputCourse.array' => 'Invalid course format.',
            ]
        );

        $scholar = ScholarUploadTemp::findorFail($id);

        $scholar->update([
            'change_school' => $data['inputSchool']['name'],
            'change_course' => $data['inputCourse']['name'],
            'change_fulladdress' => $data['inputAddress'],
            'verified_by' => Auth::user()->profile->fullname,
            'verified_at' => now(),
        ]);

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title' => 'Excel Data save',
            'message' => 'Excel data save successfully!',
        ]);
    }

    public function store(ScholarRequest $request)
    {

        try {
            DB::beginTransaction();
            $data = $request->validated();
            $path = null;
            $file = $data['files'][0];
            $import = new CheckScholarImport;
            $filename = Str::random(12) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('imports/scholars', $filename, 'public');
            Excel::import($import, storage_path('app/public/' . $path));

            $uploadedfile = ScholarUploadedFiles::create([
                'filename' => $filename,
                'filepath' => $path,
                'region_office' => Auth::user()->profile->agency_array['name'],
                'created_by' => Auth::user()->profile->fullname,
                'status' => 'pending',
            ]);
            foreach ($import->rows as $key => $value) {

                $date = $value['birthdate'];
                if (is_numeric($date)) {
                    $date = Carbon::createFromFormat('Y-m-d', '1899-12-30')
                        ->addDays($date);
                } else {
                    $date = Carbon::createFromFormat('m/d/y', $date);
                }
                $uploadedfile->temp()->create(
                    [
                        'spas_no' => $value['spas_no'],
                        'status' => $value['status'],
                        'standing' => $value['standing'],
                        'scholarship_type' => $value['scholarship_type'],
                        'scholarship_subprogram' => $value['scholarship_subprogram'],
                        'fname' => $value['fname'],
                        'lname' => $value['lname'],
                        'mname' => $value['mname'],
                        'suffix' => $value['suffix'],
                        'sex' => $value['sex'],
                        'email' => $value['email'],
                        'contact_no' => (string) $value['contact_no'],
                        'birthdate' => $date,
                        'birthplace' => $value['birthplace'],
                        'civil_status' => $value['civil_status'],
                        'address' => $value['address'] . ' ' . $value['village'],
                        'barangay' => $value['barangay'],
                        'municipality' => $value['municipality'],
                        'province' => $value['province'],
                        'region' => (string) $value['region'],
                        'year_awarded' => (string) $value['year_awarded'],
                        'course' => $value['course'],
                        'school' => $value['school'],
                        'created_by' => Auth::user()->profile->fullname,
                    ]
                );
            }

            $highTable = User::select('id')
                ->with('profile:user_id,fname,lname')
                ->whereHas('role', function ($q) {
                    $q->whereIn('name', ['Administrator']);
                })->where([
                    'is_active' => true,
                    'is_delete' => false,
                ])->get();

            foreach ($highTable as $admin) {
                $admin->notify(
                    new ScholarUploadedNotification(
                        Auth::user()->profile->fullname,
                        Auth::user()->profile->agency->name,
                    )
                );
            }

            DB::commit();

            return redirect()->back()->with('flash', [
                'status' => 'success',
                'title' => 'Excel Data save',
                'message' => 'Excel data save successfully!',
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Import Failed',
                'message' => 'There was an error importing the data: ' . $e->getMessage(),
            ]);
        }
    }

    public function insert(Request $request, string $id)
    {

        DB::beginTransaction();
        try {
            $file = ScholarUploadedFiles::where('id', Hashids::decode($id)[0] ?? 0)->first();

            $file->update([
                'validated_by' => Auth::user()->profile->fullname,
                'validated_at' => now(),
                'status' => $request['status'],
            ]);
            $fullname = $file->created_by;
            $highTable = User::with('profile:user_id,fname,lname')
                ->whereHas('profile', function ($q) use ($fullname) {
                    $q->whereRaw("LOWER(CONCAT(fname, ' ', lname)) LIKE ?", ['%' . strtolower($fullname) . '%']);
                })
                ->select('id')
                ->get();

            foreach ($highTable as $admin) {
                $admin->notify(
                    new ValidatedFilesNotification(
                        $request['status'],
                        Auth::user()->profile->fullname,
                    )
                );
            }

            Excel::import(new ScholarImport, storage_path('app/public/' . $file->filepath));
            DB::commit();

            return redirect()->back()->with('flash', [
                'status' => 'success',
                'title' => 'Scholar Information Saved!',
                'message' => 'The scholar data has been successfully saved.',
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Save Failed',
                'message' => 'There was an error saving the data: ' . $e->getMessage(),
            ]);
        }
    }

    public function gradeUpdate(Request $request, string $id)
    {

        $data = $request->validate([

            'subjects.*.grade' => 'required|array',
            'subjects.*.subject' => 'required|array',
        ]);

        DB::beginTransaction();
        try {
            $termRecordId = Hashids::decode($id)[0] ?? 0;

            foreach ($data['subjects'] as $key => $value) {

                ScholarSchoolGrades::updateOrCreate(
                    [
                        'term_record_id' => $termRecordId,
                        'subject_id' => $value['subject']['id'],
                    ],
                    [
                        'grade_id' => $value['grade']['id'],
                    ]
                );
                DB::commit();
            }

            return redirect()->back()->with('flash', [
                'status' => 'success',
                'title' => 'Grades Updated!',
                'message' => 'The grades have been successfully updated.',
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            throw ValidationException::withMessages([
                'subjects' => ['There was an error updating the grades: ' . $e->getMessage()],
            ]);
        }
    }

    public function gradeDelete(int $id)
    {
        try {
            $grade = ScholarSchoolGrades::findOrFail($id);
            $grade->update([
                'is_deleted' => true,
            ]);

            return redirect()->back()->with('flash', [
                'status' => 'success',
                'title' => 'Grade Deleted!',
                'message' => 'The grade has been successfully deleted.',
            ]);
        } catch (Exception $e) {
            throw ValidationException::withMessages([
                'subjects' => ['There was an error deleting the grade: ' . $e->getMessage()],
            ]);
        }
    }

    public function requestSubjectDenied(Request $request, string $id)
    {

        $data = $request->validate([
            'remarks' => 'required|string',
        ]);

        try {
            $requestRecord = StudentSubjectRequest::findOrFail($id);
            $requestRecord->update([
                'status' => 'rejected',
                'remarks' => $data['remarks'],
                'reviewed_at' => now(),
                'reviewed_by' => Auth::user()->profile->fullname,
            ]);

            return redirect()->back()->with('flash', [
                'status' => 'success',
                'title' => 'Request Updated!',
                'message' => 'The subject request has been successfully updated.',
            ]);
        } catch (Exception $e) {
            throw ValidationException::withMessages([
                'request' => ['There was an error updating the request: ' . $e->getMessage()],
            ]);
        }
    }

    public function requestSubjectAccept(Request $request, string $id)
    {
        try {
            $requestRecord = StudentSubjectRequest::findOrFail($id);
            $requestRecord->update([
                'status' => 'approved',
                'reviewed_at' => now(),
                'reviewed_by' => Auth::user()->profile->fullname,
            ]);

            ScholarSchoolGrades::create([
                'term_record_id' => $requestRecord->term_record_id,
                'subject_id' => $requestRecord->subject_id,
                'grade_id' => null,
            ]);

            return redirect()->back()->with('flash', [
                'status' => 'success',
                'title' => 'Request Updated!',
                'message' => 'The subject request has been successfully updated.',
            ]);
        } catch (Exception $e) {
            throw ValidationException::withMessages([
                'request' => ['There was an error updating the request: ' . $e->getMessage()],
            ]);
        }
    }

    public function publish(string $id, Request $request)
    {
        $id = Hashids::decode($id)[0] ?? 0;

        $check = ScholarUploadedFiles::where('id', $id)
            ->withCount([
                'temp',
                'temp as active_temp_count' => fn($q) =>
                $q->whereNotNull('verified_at'),
            ])
            ->first();

        if ($check->active_temp_count < $check->temp_count) {

            $check->update([
                'status' => 'Partial Publish'
            ]);
        } else {
            $check->update([
                'status' => 'Completed'
            ]);
        }


        $validatedScholar = ScholarUploadTemp::where('file_id', $id)
            ->whereNotNull('verified_at')
            ->whereNull('publish_at')
            ->get();

        if ($validatedScholar->isEmpty()) {

            return redirect()->back()->with('flash', [
                'status' => 'warn',
                'title' => 'No records found',
                'message' => 'There are no validated scholar records available for publishing.',
            ]);
        }

        DB::beginTransaction();
        //  dd($validatedScholar);
        try {
            foreach ($validatedScholar as $key => $data) {



                $data->update([
                    'publish_at' => now(),
                    'publish_by' =>  Auth::user()->profile->fullname,
                ]);


                $campus = SchoolCampusCourses::with(['course', 'campus'])
                    ->whereHas('campus', fn($q) => $q->where('generated_name', 'like', '%' . $data['change_school'] . '%'))
                    ->whereHas(
                        'course',
                        fn($q) =>
                        $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($data['change_course']) . '%'])
                    )
                    ->where('is_delete', false)
                    ->first();

                $scholars = Scholars::create([
                    'spas_no'     => trim($data['spas_no']) ?? null,
                    'type_id' => ListReferences::whereRaw('LOWER(name) = ?', [strtolower(trim($data['scholarship_type']))])
                        ->value('id') ?? null,

                    'program_id' => ListPrograms::whereRaw('LOWER(name) = ?', [strtolower(trim($data['scholarship_subprogram']))])
                        ->value('id') ?? null,

                    'category_id' => ListReferences::whereRaw('LOWER(name) = ?', [strtolower(trim($data['scholarship_subprogram']))])
                        ->value('id') ?? null,

                    'status_id' => ListStatuses::whereRaw('LOWER(name) = ?', [strtolower(trim($data['status']))])
                        ->value('id') ?? null,
                    'created_by'  => Auth::user()->profile->fullname,
                    'award_year' => $data['year_awarded']
                ]);

                $scholars->profile()->create([
                    'fname' => $data['fname'] ?? null,
                    'lname' => $data['lname'] ?? null,
                    'mname' => $data['mname'] ?? null,
                    'suffix' => $data['suffix'] ?? null,
                    'contact_no' => $data['contact_no'] ?? null,
                    'birthdate' => Carbon::parse($data['birthdate'])->setTimezone('Asia/Manila')->format('Y-m-d') ?? null,
                    'birthplace' => $data['birth_place'] ?? null,
                    'email' => $data['email'] ?? null,
                    'sex' => $data['sex'] ?? null,
                    'religion' => $data['religion'] ?? null,
                    'civil_status' => $data['civil_status'] ?? null,

                ]);


                $sliceName = explode('-', $data['change_fulladdress']['id']);

                $scholars->address()->create([
                    'address' => $data['address'],
                    'barangay_code' => $sliceName[0],
                    'municipality_code' => $sliceName[1],
                    'province_code' => $sliceName[2],
                    'region_code' => $sliceName[3],
                ]);


                $scholars->schoolInfo()->create([
                    'campus_id' => $campus->campus_id,
                    'campus_course_id' => $campus->id,
                ]);
            }

            DB::commit();
            return redirect()->back()->with('flash', [
                'status' => 'success',
                'title'  => 'Scholar data saved',
                'message' => 'The scholar information has been successfully saved and updated.',
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Publishing failed',
                'message' => $th->getMessage(),
            ]);
        }

        // dd($validatedScholar);



        // $yearLevel = SchoolCampusCourses::select('years')
        //     ->whereHas('course', function ($q) use ($data) {
        //         $q->where('name', $data['course']);
        //     })
        //     ->first();



        //  $academic_term = ListReferences::select('id', 'name')
        //      ->where('classification', $campus->term?->name)
        //      ->where('type', 'Term')
        //      ->get();



        //  $levels = ListReferences::whereIn('others', range(1, $yearLevel->years))
        //  ->pluck('id', 'others');


        // for ($i = 1; $i <= (int) $yearLevel->years; $i++) {
        //     foreach ($academic_term as $value) {
        //         $termRecords = $scholars->termRecords()->create([
        //             'scholar_school_id' => $schoolInfo->id ?? null,
        //             'term_id'           => $campus->term_id ?? null,
        //             'level_id'          => $levels[(string) $i] ?? null,
        //             'term_type_id'      => $value->id ?? null
        //         ]);
        //     }
        // }

    }

    // function update(StatusRequest $request, string $id, string $type)
    // {

    //     $data = $request->validated();
    //     $find = ListStatuses::findOrFail($id);

    //     if ($type == 'form') {
    //         $find->update([
    //             'name' => $data['name'],
    //             'icon' => $data['icon'],
    //             'type' => $data['type'],
    //             'color_id' => $data['color']['id'],
    //             'updated_by'    => Auth::user()->profile->fullname,
    //         ]);
    //     } else {
    //         $find->update([
    //             'is_active' => $data['isActive'],
    //         ]);
    //     }

    //     return redirect()->back()->with('flash', [
    //         'status' => 'success',
    //         'title'  => 'Status Updated',
    //         'message' => 'Status successfully updated.',
    //     ]);
    // }

    // function destroy(
    //     int $id
    // ) {
    //     $find = ListStatuses::findOrFail($id);
    //     $find->update([
    //         'is_delete' => true,
    //     ]);

    //     return redirect()->back()->with('flash', [
    //         'status' => 'success',
    //         'title'  => 'Status Deleted',
    //         'message' => 'Status successfully deleted.',
    //     ]);
    // }
}
