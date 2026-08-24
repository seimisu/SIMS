<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ScholarRequest;
use App\Imports\CheckScholarImport;
use App\Imports\ScholarImport;
use App\Models\ListPrograms;
use App\Models\ListReferences;
use App\Models\ListStatuses;
use App\Models\ActivityLogs;
use App\Models\LocationBarangays;
use App\Models\LocationCity;
use App\Models\LocationProvinces;
use App\Models\LocationRegions;
use App\Models\Scholars;
use App\Models\ScholarSchoolGrades;
use App\Models\ScholarSchoolInfos;
use App\Models\ScholarProfiles;
use App\Models\ScholarTerm;
use App\Models\ScholarUploadedFiles;
use App\Models\ScholarUploadTemp;
use App\Models\SchoolCampusCourses;
use App\Models\SchoolCampuses;
use App\Models\User;
use App\Notifications\ScholarUploadedNotification;
use App\Notifications\ValidatedFilesNotification;
use App\References\LocationClass;
use App\Support\SystemPermissions;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Vinkla\Hashids\Facades\Hashids;

use function Symfony\Component\Clock\now;

class ScholarReviewController extends Controller
{
    public function index(Request $request, LocationClass $location)
    {
        $selectedFileId = $request->input('id') ? (Hashids::decode($request->input('id'))[0] ?? 0) : 0;

        return Inertia::render('Web/reviewPage', [
            'files' => ScholarUploadedFiles::whereNot('status', 'reject')->withCount([
                'temp',
                'temp as active_temp_count' => fn ($q) => $q->where('row_status', 'valid'),
            ])->orderBy('id', 'desc')->paginate(10),
            'selected' => $selectedFileId ? ScholarUploadTemp::where('file_id', $selectedFileId)
                ->whereNull('publish_at')
                ->orderBy('id', 'ASC')
                ->get()->map(function ($scholar) {
                    return [
                        'id' => $scholar->id,
                        'row_number' => $scholar->row_number,
                        'spas_no' => $scholar->spas_no,
                        'fullname' => $scholar->fullname,
                        'school' => $scholar->school,
                        'course' => $scholar->course,
                        'scholarship_type' => $scholar->scholarship_type,
                        'scholarship_subprogram' => $scholar->scholarship_subprogram,
                        'fname' => $scholar->fname,
                        'lname' => $scholar->lname,
                        'mname' => $scholar->mname,
                        'suffix' => $scholar->suffix,
                        'sex' => $scholar->sex,
                        'email' => $scholar->email,
                        'contact_no' => $scholar->contact_no,
                        'birthdate' => $scholar->birthdate?->format('Y-m-d'),
                        'birthplace' => $scholar->birthplace,
                        'civil_status' => $scholar->civil_status,
                        'year_awarded' => $scholar->year_awarded,
                        'address' => $scholar->address,
                        'barangay' => $scholar->barangay,
                        'municipality' => $scholar->municipality,
                        'region' => $scholar->region,
                        'province' => $scholar->province,
                        'matchedSchool' => $scholar->matched_school_id ? [
                            'id' => $scholar->matched_school_id,
                            'name' => $scholar->matched_school_name,
                        ] : null,
                        'matchedCourse' => $scholar->matched_course_id ? [
                            'id' => $scholar->matched_course_id,
                            'name' => $scholar->matched_course_name,
                            'campus' => $scholar->matched_course_campus,
                        ] : null,
                        'matchedAddress' => $scholar->matched_address,
                        'status' => $scholar->status,
                        'row_status' => $scholar->row_status,
                        'validation_errors' => $scholar->validation_errors ?? [],
                        'loading' => false,
                        'error1' => null,
                        'error2' => null,
                        'error3' => null,
                        'verified_at' => $scholar->verified_at ? Carbon::parse($scholar->verified_at)->diffForHumans() : null,
                        'verified_by' => $scholar->verified_by,
                        'publish_at' => $scholar->publish_at ? Carbon::parse($scholar->publish_at)->diffForHumans() : null,
                        'publish_by' => $scholar->publish_by,
                    ];
                }) : [],
            'validationStatus' => $request->input('id') ? ScholarUploadedFiles::whereNot('status', 'reject')->withCount([
                'temp',
                'temp as active_temp_count' => fn ($q) => $q->where('row_status', 'valid'),
            ])->where('id', $selectedFileId)->get()
                ->map(fn ($item) => [
                    'completed' => $item->active_temp_count ?? 0,
                    'total' => $item->temp_count ?? 0,
                ])
                ->first() : [],
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
        $blockingErrors = collect($scholar->validation_errors ?? [])
            ->reject(fn ($error) => Str::contains($error, [
                'was not found in the database',
                'was not found for school',
            ]))
            ->values();

        if ($blockingErrors->isNotEmpty()) {
            throw ValidationException::withMessages([
                'row' => $blockingErrors->all(),
            ]);
        }

        $scholar->update([
            'change_school' => $data['inputSchool']['name'],
            'change_course' => $data['inputCourse']['name'],
            'change_fulladdress' => $data['inputAddress'],
            'verified_by' => Auth::user()->profile->fullname,
            'verified_at' => now(),
            'row_status' => 'valid',
            'validation_errors' => [],
        ]);

        if ($scholar->file) {
            $this->syncImportBatchCounts($scholar->file);
        }

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title' => 'Excel Data save',
            'message' => 'Excel data save successfully!',
        ]);
    }

    public function store(ScholarRequest $request)
    {
        $path = null;

        try {
            DB::beginTransaction();
            $data = $request->validated();
            $file = $data['files'][0];
            $import = new CheckScholarImport;
            $filename = Str::random(12).'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('imports/scholars', $filename, 'public');
            Excel::import($import, storage_path('app/public/'.$path));
            $this->validateImportHeaders($import->rows);
            $duplicateLookups = $this->duplicateLookupsForImport($import->rows);

            $uploadedfile = ScholarUploadedFiles::create([
                'filename' => $filename,
                'filepath' => $path,
                'region_office' => Auth::user()->profile->agency_array['name'],
                'created_by' => Auth::user()->profile->fullname,
                'status' => 'pending',
            ]);
            foreach ($import->rows as $key => $value) {
                $rowNumber = $import->rowNumbers[$key] ?? ($key + 2);
                $validation = $this->validateImportRow($value, $rowNumber, null, $duplicateLookups);
                $date = $this->parseImportDate($value['birthdate'] ?? null);
                $uploadedfile->temp()->create(
                    [
                        'row_number' => $rowNumber,
                        'spas_no' => $value['spas_no'],
                        'status' => $value['status'],
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
                        'address' => $value['address'],
                        'barangay' => $value['barangay'],
                        'municipality' => $value['municipality'],
                        'province' => $value['province'],
                        'region' => (string) $value['region'],
                        'year_awarded' => (string) $value['year_awarded'],
                        'course' => $value['course'],
                        'school' => $value['school'],
                        'created_by' => Auth::user()->profile->fullname,
                        'row_status' => $validation['status'],
                        'validation_errors' => $validation['errors'],
                        'matched_school_id' => $validation['matches']['school_id'],
                        'matched_school_name' => $validation['matches']['school_name'],
                        'matched_campus_id' => $validation['matches']['campus_id'],
                        'matched_course_id' => $validation['matches']['course_id'],
                        'matched_course_name' => $validation['matches']['course_name'],
                        'matched_course_campus' => $validation['matches']['course_campus'],
                        'matched_address' => $validation['matches']['address'],
                    ]
                );
            }

            $this->syncImportBatchCounts($uploadedfile);
            $uploadedfile->refresh();

            if ($uploadedfile->valid_rows < $uploadedfile->total_rows) {
                $uploadedfile->update([
                    'status' => 'Needs Correction',
                ]);

                DB::commit();

                return redirect()->back()->with('flash', [
                    'status' => 'warn',
                    'title' => 'Validation Preview Created',
                    'message' => "{$uploadedfile->valid_rows} of {$uploadedfile->total_rows} rows passed validation. Open the preview to see row issues, then fix the Excel file and upload it again.",
                ]);
            }

            $uploadedfile->update([
                'status' => 'Ready',
            ]);

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
                'title' => 'Ready to Publish',
                'message' => 'All rows passed validation. Open the preview and publish the valid batch.',
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            throw $e;
        } catch (Exception $e) {
            DB::rollBack();
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            Log::error('Scholar import failed after file passed request validation.', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);

            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Import Failed',
                'message' => 'The file was readable, but the import preview could not be created. Please try again or contact support with the latest log entry.',
            ]);
        }
    }

    public function template()
    {
        $headers = [
            'spas_no',
            'status',
            'scholarship_type',
            'scholarship_subprogram',
            'fname',
            'lname',
            'mname',
            'suffix',
            'sex',
            'email',
            'contact_no',
            'birthdate',
            'birthplace',
            'civil_status',
            'address',
            'barangay',
            'municipality',
            'province',
            'region',
            'year_awarded',
            'course',
            'school',
        ];

        $sample = [
            'U-2024-07-13132',
            'ONGOING',
            'Undergraduate',
            'MERIT',
            'TRISTAN JAMES',
            'TOLENTINO',
            'YUCOT',
            '',
            'M',
            'sample.scholar@example.com',
            '09472546834',
            '2003-09-22',
            'CEBU CITY',
            'SINGLE',
            'TUBOD',
            'VALLADOLID',
            'CARCAR CITY',
            'CEBU',
            '7',
            '2024',
            'BACHELOR OF SCIENCE IN COMPUTER SCIENCE',
            'CEBU INSTITUTE OF TECHNOLOGY-UNIVERSITY',
        ];

        $notes = [
            'Required: spas_no, status, scholarship_type, fname, lname, sex, email, birthdate, civil_status, year_awarded, course, school.',
            'Sex must be M or F.',
            'Use YYYY-MM-DD for birthdate, or use a real Excel date cell.',
            'Use text format for contact_no to preserve leading zeroes.',
            'School, course, and address will be matched in Scholar Import Review.',
        ];

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Scholars');

        foreach ($headers as $index => $header) {
            $sheet->setCellValue([$index + 1, 1], $header);
            $sheet->getColumnDimensionByColumn($index + 1)->setAutoSize(true);
        }

        foreach ($sample as $index => $value) {
            $sheet->setCellValueExplicit([$index + 1, 2], $value, DataType::TYPE_STRING);
        }

        $sheet->setCellValue('A4', 'Notes');
        foreach ($notes as $index => $note) {
            $sheet->setCellValue('A'.($index + 5), $note);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 'scholar_import_template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
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
                    $q->whereRaw("LOWER(CONCAT(fname, ' ', lname)) LIKE ?", ['%'.strtolower($fullname).'%']);
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

            Excel::import(new ScholarImport, storage_path('app/public/'.$file->filepath));
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
                'message' => 'There was an error saving the data: '.$e->getMessage(),
            ]);
        }
    }

    public function gradeUpdate(Request $request, string $id)
    {
        $permissions = app(SystemPermissions::class);
        if (! $permissions->can(Auth::user(), 'scholars.update')) {
            abort(403, 'Unauthorized');
        }

        $data = $request->validate([
            'school' => ['nullable', 'array'],
            'school.id' => ['nullable', 'integer'],
            'course' => ['nullable', 'array'],
            'course.id' => ['nullable', 'integer'],
            'level' => ['nullable', 'array'],
            'level.id' => ['nullable', 'integer'],
            'term' => ['nullable', 'array'],
            'term.id' => ['nullable', 'integer'],
            'academic_year' => ['nullable', 'string', 'max:20'],
            'scholarship_status' => ['nullable'],
            'subjects' => ['required', 'array'],
            'deleted_subjects' => ['nullable', 'array'],
            'deleted_subjects.*' => ['integer'],
            'subjects.*.id' => ['nullable', 'integer'],
            'subjects.*.grade' => ['required', 'array'],
            'subjects.*.grade.id' => ['required', 'integer'],
            'subjects.*.subject' => ['required', 'array'],
            'subjects.*.subject.id' => ['required', 'integer'],
        ]);

        DB::beginTransaction();
        try {
            $termRecordId = Hashids::decode($id)[0] ?? (ctype_digit($id) ? (int) $id : 0);
            $termRecord = ScholarTerm::with([
                'level',
                'term',
                'schoolInfo.campus.address',
                'schoolInfo.course.course',
                'scholar',
                'subjects.subject',
                'subjects.grade',
            ])->findOrFail($termRecordId);
            $previousAcademicLog = $this->academicRecordLogSnapshot($termRecord);

            if ($permissions->shouldScopeToRegion(Auth::user())) {
                $regionCode = $termRecord->schoolInfo?->campus?->address?->region_code;
                if ($regionCode !== $permissions->regionCodeFor(Auth::user())) {
                    abort(403, 'Unauthorized');
                }
            }

            if (! empty($data['school']['id']) || ! empty($data['course']['id'])) {
                $schoolInfo = ScholarSchoolInfos::updateOrCreate(
                    [
                        'id' => $termRecord->scholar_school_id,
                        'scholar_id' => $termRecord->scholar_id,
                    ],
                    [
                        'campus_id' => $data['school']['id'] ?? $termRecord->schoolInfo?->campus_id,
                        'campus_course_id' => $data['course']['id'] ?? $termRecord->schoolInfo?->campus_course_id,
                    ]
                );

                $termRecord->scholar_school_id = $schoolInfo->id;
            }

            $termRecord->fill([
                'term_id' => $data['term']['id'] ?? $termRecord->term_id,
                'level_id' => $data['level']['id'] ?? $termRecord->level_id,
                'academic_year' => $data['academic_year'] ?? $termRecord->academic_year,
            ])->save();

            if (! empty($data['deleted_subjects'])) {
                ScholarSchoolGrades::where('term_record_id', $termRecordId)
                    ->whereIn('id', $data['deleted_subjects'])
                    ->update(['is_deleted' => true]);
            }

            foreach ($data['subjects'] as $key => $value) {

                if (! empty($value['id'])) {
                    ScholarSchoolGrades::where('term_record_id', $termRecordId)
                        ->whereKey($value['id'])
                        ->update([
                            'subject_id' => $value['subject']['id'],
                            'grade_id' => $value['grade']['id'],
                            'is_deleted' => false,
                        ]);

                    continue;
                }

                ScholarSchoolGrades::updateOrCreate(
                    [
                        'term_record_id' => $termRecordId,
                        'subject_id' => $value['subject']['id'],
                    ],
                    [
                        'grade_id' => $value['grade']['id'],
                        'is_deleted' => false,
                    ]
                );
            }

            if (array_key_exists('scholarship_status', $data)) {
                $status = is_array($data['scholarship_status'])
                    ? ($data['scholarship_status']['name'] ?? $data['scholarship_status']['id'] ?? null)
                    : $data['scholarship_status'];

                DB::connection('scholars')
                    ->table('scholar_processes')
                    ->updateOrInsert(
                        ['term_record_id' => $termRecord->id],
                        [
                            'scholar_id' => $termRecord->scholar_id,
                            'scholarship_status' => $status ? Str::upper($status) : null,
                            'submission' => 'APPROVED',
                            'payroll' => 'NOT SUBMITTED',
                            'is_end' => false,
                            'updated_at' => now(),
                            'updated_by' => Auth::user()?->profile?->fullname,
                        ]
                    );
            }

            $updatedAcademicLog = $this->academicRecordLogSnapshot(
                $termRecord->fresh([
                    'level',
                    'term',
                    'schoolInfo.campus.address',
                    'schoolInfo.course.course',
                    'scholar',
                    'subjects.subject',
                    'subjects.grade',
                ])
            );

            $academicLogChanges = $this->academicRecordLogChanges(
                $previousAcademicLog,
                $updatedAcademicLog
            );

            if (! empty($academicLogChanges['changes'])) {
                ActivityLogs::create([
                    'previous_data' => $academicLogChanges['previous'],
                    'changes_data' => $academicLogChanges['changes'],
                    'request_type' => 'academic',
                    'created_by' => Auth::user()?->profile?->fullname,
                    'scholar_id' => $termRecord->scholar_id,
                ]);
            }

            DB::commit();

            return redirect()->back()->with('flash', [
                'status' => 'success',
                'title' => 'Academic Record Updated!',
                'message' => 'The academic record has been successfully updated.',
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            throw ValidationException::withMessages([
                'subjects' => ['There was an error updating the grades: '.$e->getMessage()],
            ]);
        }
    }

    private function academicRecordLogSnapshot(ScholarTerm $termRecord): array
    {
        $scholarshipStatus = DB::connection('scholars')
            ->table('scholar_processes')
            ->where('term_record_id', $termRecord->id)
            ->value('scholarship_status');

        return [
            'school' => $termRecord->schoolInfo?->campus?->generated_name,
            'course' => $termRecord->schoolInfo?->course?->course?->name,
            'year_level' => $termRecord->level?->name,
            'semester' => $termRecord->term?->name,
            'academic_year' => $termRecord->academic_year,
            'scholarship_status' => $scholarshipStatus,
            'subjects' => $termRecord->subjects
                ->sortBy(fn ($subject) => $subject->id)
                ->values()
                ->map(fn ($subject) => [
                    'id' => $subject->id,
                    'subject_id' => $subject->subject_id,
                    'grade_id' => $subject->grade_id,
                    'label' => $this->academicRecordSubjectLogLabel($subject),
                ])
                ->all(),
        ];
    }

    private function academicRecordSubjectLogLabel(ScholarSchoolGrades $subject): string
    {
        $code = $subject->subject?->subject_code ?? 'No Code';
        $name = $subject->subject?->name ?? 'No Subject';
        $unit = $subject->subject?->unit ?? '-';
        $grade = $subject->grade?->grade ?? '-';

        return "{$code} - {$name} | {$unit} unit(s) | Grade: {$grade}";
    }

    private function academicRecordLogChanges(array $previous, array $updated): array
    {
        $previousData = [];
        $changesData = [];

        foreach ([
            'school',
            'course',
            'year_level',
            'semester',
            'academic_year',
            'scholarship_status',
        ] as $field) {
            if (($previous[$field] ?? null) !== ($updated[$field] ?? null)) {
                $previousData[$field] = $previous[$field] ?? 'Not Set';
                $changesData[$field] = $updated[$field] ?? 'Removed';
            }
        }

        $previousSubjects = collect($previous['subjects'] ?? [])->keyBy('id');
        $updatedSubjects = collect($updated['subjects'] ?? [])->keyBy('id');

        $updatedSubjectChanges = [];
        foreach ($updatedSubjects as $id => $subject) {
            $oldSubject = $previousSubjects->get($id);
            if (! $oldSubject) {
                continue;
            }

            if (
                ($oldSubject['subject_id'] ?? null) !== ($subject['subject_id'] ?? null)
                || ($oldSubject['grade_id'] ?? null) !== ($subject['grade_id'] ?? null)
            ) {
                $updatedSubjectChanges[] = [
                    'previous' => $oldSubject['label'],
                    'updated' => $subject['label'],
                ];
            }
        }

        if (! empty($updatedSubjectChanges)) {
            $previousData['updated_subjects'] = collect($updatedSubjectChanges)
                ->pluck('previous')
                ->implode("\n");
            $changesData['updated_subjects'] = collect($updatedSubjectChanges)
                ->pluck('updated')
                ->implode("\n");
        }

        $addedSubjects = $updatedSubjects
            ->reject(fn ($subject, $id) => $previousSubjects->has($id))
            ->pluck('label')
            ->implode("\n");

        if ($addedSubjects !== '') {
            $previousData['added_subjects'] = 'Not Set';
            $changesData['added_subjects'] = $addedSubjects;
        }

        $removedSubjects = $previousSubjects
            ->reject(fn ($subject, $id) => $updatedSubjects->has($id))
            ->pluck('label')
            ->implode("\n");

        if ($removedSubjects !== '') {
            $previousData['removed_subjects'] = $removedSubjects;
            $changesData['removed_subjects'] = 'Removed';
        }

        return [
            'previous' => $previousData,
            'changes' => $changesData,
        ];
    }

    public function gradeDelete(int $id)
    {
        try {
            $permissions = app(SystemPermissions::class);
            if (! $permissions->can(Auth::user(), 'scholars.update')) {
                abort(403, 'Unauthorized');
            }

            $grade = ScholarSchoolGrades::with('termRecord.schoolInfo.campus.address')->findOrFail($id);

            if ($permissions->shouldScopeToRegion(Auth::user())) {
                $regionCode = $grade->termRecord?->schoolInfo?->campus?->address?->region_code;
                if ($regionCode !== $permissions->regionCodeFor(Auth::user())) {
                    abort(403, 'Unauthorized');
                }
            }

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
                'subjects' => ['There was an error deleting the grade: '.$e->getMessage()],
            ]);
        }
    }

    public function publish(string $id, Request $request)
    {
        $id = Hashids::decode($id)[0] ?? 0;

        $check = ScholarUploadedFiles::where('id', $id)
            ->withCount([
                'temp',
                'temp as active_temp_count' => fn ($q) => $q->where('row_status', 'valid'),
            ])
            ->first();

        if (! $check) {
            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'File not found',
                'message' => 'The selected import batch could not be found.',
            ]);
        }

        if ($check->active_temp_count !== $check->temp_count) {
            return redirect()->back()->with('flash', [
                'status' => 'warn',
                'title' => 'Import blocked',
                'message' => 'This file cannot be imported until all rows are valid. Fix the Excel file and upload it again.',
            ]);
        }

        $validatedScholar = ScholarUploadTemp::where('file_id', $id)
            ->whereNull('publish_at')
            ->where('row_status', 'valid')
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
                    'publish_by' => Auth::user()->profile->fullname,
                ]);

                $campusId = $data->matched_campus_id;
                $campusCourseId = $data->matched_course_id;
                $address = $data->matched_address;

                if (! $campusId || ! $campusCourseId || ! $address) {
                    $campus = $this->matchedCourse($data['course'], $data['school']);
                    $address = $this->matchedAddress($data);
                    $campusId = $campus?->campus_id;
                    $campusCourseId = $campus?->id;
                }

                if (! $campusId || ! $campusCourseId || ! $address) {
                    throw new Exception("Row {$data->row_number}: validated row no longer matches current school, course, or address records.");
                }

                $scholars = Scholars::create([
                    'spas_no' => trim($data['spas_no']) ?? null,
                    'type_id' => ListReferences::whereRaw('LOWER(name) = ?', [strtolower(trim($data['scholarship_type']))])
                        ->value('id') ?? null,

                    'program_id' => ListPrograms::whereRaw('LOWER(name) = ?', [strtolower(trim($data['scholarship_subprogram']))])
                        ->value('id') ?? null,

                    'category_id' => ListReferences::whereRaw('LOWER(name) = ?', [strtolower(trim($data['scholarship_subprogram']))])
                        ->value('id') ?? null,

                    'status_id' => ListStatuses::whereRaw('LOWER(name) = ?', [strtolower(trim($data['status']))])
                        ->value('id') ?? null,
                    'academic_status' => in_array(trim($data['status'] ?? ''), ['Ongoing', 'Graduating', 'Graduated', 'LOA', 'Terminated'], true)
                        ? trim($data['status'])
                        : 'Ongoing',
                    'created_by' => Auth::user()->profile->fullname,
                    'award_year' => $data['year_awarded'],
                ]);

                $scholars->profile()->create([
                    'fname' => $data['fname'] ?? null,
                    'lname' => $data['lname'] ?? null,
                    'mname' => $data['mname'] ?? null,
                    'suffix' => $data['suffix'] ?? null,
                    'contact_no' => $data['contact_no'] ?? null,
                    'birthdate' => Carbon::parse($data['birthdate'])->setTimezone('Asia/Manila')->format('Y-m-d') ?? null,
                    'birthplace' => $data['birthplace'] ?? null,
                    'email' => $data['email'] ?? null,
                    'sex' => $data['sex'] ?? null,
                    'religion' => $data['religion'] ?? null,
                    'civil_status' => $data['civil_status'] ?? null,

                ]);

                $scholars->address()->create([
                    'address' => $data['address'],
                    'barangay_code' => $address['barangay_code'],
                    'municipality_code' => $address['municipality_code'],
                    'province_code' => $address['province_code'],
                    'region_code' => $address['region_code'],
                ]);

                $scholars->schoolInfo()->create([
                    'campus_id' => $campusId,
                    'campus_course_id' => $campusCourseId,
                ]);
            }

            $this->syncImportBatchCounts($check->fresh());
            $check->fresh()->update([
                'status' => 'Completed',
            ]);

            DB::commit();

            return redirect()->back()->with('flash', [
                'status' => 'success',
                'title' => 'Scholar data saved',
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

    public function destroy(string $id, string $type)
    {
        $fileId = Hashids::decode($id)[0] ?? 0;
        $file = ScholarUploadedFiles::findOrFail($fileId);

        if (Str::lower((string) $file->status) === 'completed') {
            return redirect()->back()->with('flash', [
                'status' => 'warn',
                'title' => 'Import already completed',
                'message' => 'Completed import batches cannot be deleted.',
            ]);
        }

        $file->update([
            'status' => 'reject',
        ]);

        return redirect()->route('review')->with('flash', [
            'status' => 'success',
            'title' => 'Import batch deleted',
            'message' => 'The import batch was removed from the review list. Upload the corrected Excel file again.',
        ]);
    }

    private function validateImportRow($row, int $rowNumber, ?int $ignoreTempId = null, array $duplicateLookups = []): array
    {
        $data = collect($row)->map(fn ($value) => is_string($value) ? trim($value) : $value)->all();
        $errors = [];

        $required = [
            'spas_no',
            'status',
            'scholarship_type',
            'fname',
            'lname',
            'sex',
            'email',
            'birthdate',
            'civil_status',
            'year_awarded',
            'course',
            'school',
        ];

        foreach ($required as $field) {
            if (! filled($data[$field] ?? null)) {
                $errors[] = Str::headline($field).' is required.';
            }
        }

        if (filled($data['sex'] ?? null) && ! in_array(Str::upper($data['sex']), ['M', 'F'], true)) {
            $errors[] = 'Sex must be M or F.';
        }

        if (filled($data['email'] ?? null) && Validator::make(['email' => $data['email']], ['email' => 'email'])->fails()) {
            $errors[] = 'Email format is invalid.';
        }

        if (filled($data['birthdate'] ?? null) && ! $this->parseImportDate($data['birthdate'])) {
            $errors[] = 'Birthdate must be a valid Excel date or YYYY-MM-DD date.';
        }

        $duplicate = false;
        if (filled($data['spas_no'] ?? null)) {
            $normalizedSpas = Str::lower(trim($data['spas_no']));
            $duplicate = ($duplicateLookups['spas_no'][$normalizedSpas] ?? 0) > 1
                || Scholars::whereRaw('LOWER(spas_no) = ?', [$normalizedSpas])->exists()
                || ScholarUploadTemp::whereRaw('LOWER(spas_no) = ?', [$normalizedSpas])
                    ->when($ignoreTempId, fn ($q) => $q->where('id', '!=', $ignoreTempId))
                    ->whereHas('file', fn ($q) => $q->whereNot('status', 'reject'))
                    ->exists();
            if ($duplicate) {
                $errors[] = ($duplicateLookups['spas_no'][$normalizedSpas] ?? 0) > 1
                    ? 'SPAS No is duplicated within this Excel file.'
                    : 'SPAS No already exists.';
            }
        }

        if (filled($data['email'] ?? null)) {
            $normalizedEmail = Str::lower(trim($data['email']));
            $emailDuplicate = ($duplicateLookups['email'][$normalizedEmail] ?? 0) > 1
                || ScholarProfiles::whereRaw('LOWER(email) = ?', [$normalizedEmail])->exists()
                || ScholarUploadTemp::whereRaw('LOWER(email) = ?', [$normalizedEmail])
                    ->when($ignoreTempId, fn ($q) => $q->where('id', '!=', $ignoreTempId))
                    ->whereHas('file', fn ($q) => $q->whereNot('status', 'reject'))
                    ->exists();
            if ($emailDuplicate) {
                $duplicate = true;
                $errors[] = ($duplicateLookups['email'][$normalizedEmail] ?? 0) > 1
                    ? 'Email is duplicated within this Excel file.'
                    : 'Email already exists.';
            }
        }

        if (filled($data['status'] ?? null) && ! ListStatuses::whereRaw('LOWER(name) = ?', [Str::lower($data['status'])])->exists()) {
            $errors[] = "Status '{$data['status']}' was not found in the database.";
        }

        if (filled($data['scholarship_type'] ?? null) && ! ListReferences::whereRaw('LOWER(name) = ?', [Str::lower($data['scholarship_type'])])->exists()) {
            $errors[] = "Scholarship type '{$data['scholarship_type']}' was not found in the database.";
        }

        if (filled($data['scholarship_subprogram'] ?? null) && ! ListPrograms::whereRaw('LOWER(name) = ?', [Str::lower($data['scholarship_subprogram'])])->exists()) {
            $errors[] = "Scholarship subprogram '{$data['scholarship_subprogram']}' was not found in the database.";
        }

        $schoolMatch = $this->matchedSchool($data['school'] ?? null);
        $courseMatch = $this->matchedCourse($data['course'] ?? null, $data['school'] ?? null);
        $locationValidation = $this->validateLocationMatch($data);

        if (filled($data['school'] ?? null) && ! $schoolMatch) {
            $errors[] = "School '{$data['school']}' was not found in the database.";
        }

        if (filled($data['course'] ?? null) && ! $courseMatch) {
            $errors[] = "Course '{$data['course']}' was not found for school '{$data['school']}'.";
        }

        if (! $locationValidation['matched']) {
            $errors[] = $locationValidation['message'];
        }

        $missingRequired = collect($required)->contains(fn ($field) => ! filled($data[$field] ?? null));
        $needsCorrection = ! empty($errors);

        return [
            'status' => match (true) {
                $missingRequired => 'missing_required',
                $duplicate => 'duplicate',
                $needsCorrection => 'needs_correction',
                default => 'valid',
            },
            'errors' => array_values(array_unique(
                collect($errors)->map(fn ($error) => "Row {$rowNumber}: {$error}")->all()
            )),
            'matches' => [
                'school_id' => $schoolMatch?->id,
                'school_name' => $schoolMatch?->generated_name,
                'campus_id' => $courseMatch?->campus_id,
                'course_id' => $courseMatch?->id,
                'course_name' => $courseMatch?->course ? Str::upper($courseMatch->course->name) : null,
                'course_campus' => $courseMatch?->campus?->generated_name,
                'address' => $locationValidation['address'],
            ],
        ];
    }

    private function duplicateLookupsForImport($rows): array
    {
        $spasNumbers = [];
        $emails = [];

        foreach ($rows as $row) {
            $spasNo = Str::lower(trim((string) ($row['spas_no'] ?? '')));
            $email = Str::lower(trim((string) ($row['email'] ?? '')));

            if ($spasNo !== '') {
                $spasNumbers[$spasNo] = ($spasNumbers[$spasNo] ?? 0) + 1;
            }

            if ($email !== '') {
                $emails[$email] = ($emails[$email] ?? 0) + 1;
            }
        }

        return [
            'spas_no' => $spasNumbers,
            'email' => $emails,
        ];
    }

    private function validateImportHeaders($rows): void
    {
        $requiredHeaders = [
            'spas_no',
            'status',
            'scholarship_type',
            'scholarship_subprogram',
            'fname',
            'lname',
            'mname',
            'suffix',
            'sex',
            'email',
            'contact_no',
            'birthdate',
            'birthplace',
            'civil_status',
            'address',
            'barangay',
            'municipality',
            'province',
            'region',
            'year_awarded',
            'course',
            'school',
        ];

        $firstRow = $rows?->first();
        if (! $firstRow) {
            throw ValidationException::withMessages([
                'files' => ['The uploaded Excel file has no scholar rows.'],
            ]);
        }

        $actualHeaders = collect($firstRow->keys())
            ->map(fn ($header) => trim((string) $header))
            ->filter()
            ->values();

        $missingHeaders = collect($requiredHeaders)->diff($actualHeaders)->values();
        $unknownHeaders = $actualHeaders->diff($requiredHeaders)->values();
        $wrongOrder = $missingHeaders->isEmpty()
            && $unknownHeaders->isEmpty()
            && $actualHeaders->values()->all() !== $requiredHeaders;

        if ($missingHeaders->isNotEmpty() || $unknownHeaders->isNotEmpty() || $wrongOrder) {
            $messages = [];

            if ($missingHeaders->isNotEmpty()) {
                $messages[] = 'Missing headers: '.$missingHeaders->implode(', ');
            }

            if ($unknownHeaders->isNotEmpty()) {
                $messages[] = 'Unexpected headers: '.$unknownHeaders->implode(', ');
            }

            if ($wrongOrder) {
                $messages[] = 'Headers must follow this exact order: '.implode(', ', $requiredHeaders);
            }

            throw ValidationException::withMessages([
                'files' => $messages,
            ]);
        }
    }

    private function parseImportDate($value): ?Carbon
    {
        if (! filled($value)) {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Carbon::createFromFormat('Y-m-d', '1899-12-30')->addDays((int) $value);
            }

            foreach (['Y-m-d', 'm/d/Y', 'm/d/y'] as $format) {
                try {
                    return Carbon::createFromFormat($format, trim((string) $value));
                } catch (\Throwable) {
                    //
                }
            }

            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function matchedSchool(?string $school): ?SchoolCampuses
    {
        $normalizedSchool = $this->normalizeImportLookupString($school);

        if (! filled($normalizedSchool)) {
            return null;
        }

        return SchoolCampuses::where('is_delete', false)
            ->where('is_active', true)
            ->whereRaw("LOWER(regexp_replace(generated_name, '[^[:alnum:]]', '', 'g')) LIKE ?", ['%'.$normalizedSchool.'%'])
            ->orderByRaw('LENGTH(generated_name) ASC')
            ->first();
    }

    private function matchedCourse(?string $course, ?string $school): ?SchoolCampusCourses
    {
        if (! filled($course) || ! filled($school)) {
            return null;
        }

        return SchoolCampusCourses::with(['course', 'campus'])
            ->where('is_delete', false)
            ->where('is_active', true)
            ->whereHas('course', fn ($q) => $q->whereRaw('LOWER(name) LIKE ?', ['%'.Str::lower(trim($course)).'%']))
            ->whereHas('campus', fn ($campus) => $this->whereNormalizedSchoolName($campus, $school))
            ->first();
    }

    private function whereNormalizedSchoolName($query, ?string $school)
    {
        return $query->whereRaw(
            "LOWER(regexp_replace(generated_name, '[^[:alnum:]]', '', 'g')) LIKE ?",
            ['%'.$this->normalizeImportLookupString($school).'%']
        );
    }

    private function normalizeImportLookupString(?string $value): string
    {
        return preg_replace('/[^a-z0-9]/', '', Str::lower(trim((string) $value))) ?? '';
    }

    private function matchedAddress($data): ?array
    {
        return $this->validateLocationMatch($data)['address'];
    }

    private function validateLocationMatch($data): array
    {
        $region = trim((string) data_get($data, 'region', ''));
        $province = trim((string) data_get($data, 'province', ''));
        $municipality = trim((string) data_get($data, 'municipality', ''));
        $barangay = trim((string) data_get($data, 'barangay', ''));

        if (! filled($region)) {
            return $this->locationValidationResult("Region is required for location matching. Address Line is treated as free text.");
        }

        if (! filled($province)) {
            return $this->locationValidationResult("Province is required for location matching. Address Line is treated as free text.");
        }

        if (! filled($municipality)) {
            return $this->locationValidationResult("Municipality is required for location matching. Address Line is treated as free text.");
        }

        if (! filled($barangay)) {
            return $this->locationValidationResult("Barangay is required for location matching. Address Line is treated as free text.");
        }

        $regionRecord = $this->matchedRegion($region);

        if (! $regionRecord) {
            return $this->locationValidationResult("Region '{$region}' was not found in the database.");
        }

        $provinceRecord = LocationProvinces::where('region_code', $regionRecord->code)
            ->whereRaw('LOWER(name) = ?', [Str::lower($province)])
            ->first();

        if (! $provinceRecord) {
            return $this->locationValidationResult("Province '{$province}' was not found under region '{$region}'.");
        }

        $cityRecord = LocationCity::where('province_code', $provinceRecord->code)
            ->whereRaw('LOWER(name) = ?', [Str::lower($municipality)])
            ->first();

        if (! $cityRecord) {
            return $this->locationValidationResult("Municipality '{$municipality}' was not found under province '{$province}'.");
        }

        $barangayRecord = LocationBarangays::where('municipality_code', $cityRecord->code)
            ->whereRaw('LOWER(name) = ?', [Str::lower($barangay)])
            ->first();

        if (! $barangayRecord) {
            return $this->locationValidationResult("Barangay '{$barangay}' was not found under municipality '{$municipality}'.");
        }

        return $this->locationValidationResult(null, [
            'id' => "{$barangayRecord->code}-{$cityRecord->code}-{$provinceRecord->code}-{$regionRecord->code}",
            'name' => "{$barangayRecord->name}, {$cityRecord->name}, {$provinceRecord->name}, {$regionRecord->region}",
            'barangay_code' => $barangayRecord->code,
            'municipality_code' => $cityRecord->code,
            'province_code' => $provinceRecord->code,
            'region_code' => $regionRecord->code,
        ]);
    }

    private function locationValidationResult(?string $message, ?array $address = null): array
    {
        return [
            'matched' => $address !== null,
            'message' => $message,
            'address' => $address,
        ];
    }

    private function matchedRegion(string $region): ?LocationRegions
    {
        $normalizedRegion = Str::lower(trim($region));

        return LocationRegions::query()
            ->where('code', $region)
            ->orWhereRaw('LOWER(name) = ?', [$normalizedRegion])
            ->orWhereRaw('LOWER(region) = ?', [$normalizedRegion])
            ->when(ctype_digit($region), function ($query) use ($region) {
                $query->orWhere('code', 'LIKE', str_pad($region, 2, '0', STR_PAD_LEFT).'%');
            })
            ->first();
    }

    private function syncImportBatchCounts(ScholarUploadedFiles $file): void
    {
        $counts = $file->temp()
            ->select('row_status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('row_status')
            ->pluck('aggregate', 'row_status');

        $published = $file->temp()->whereNotNull('publish_at')->count();

        $file->update([
            'total_rows' => $file->temp()->count(),
            'valid_rows' => (int) ($counts['valid'] ?? 0),
            'needs_correction_rows' => (int) ($counts['needs_correction'] ?? 0),
            'duplicate_rows' => (int) ($counts['duplicate'] ?? 0),
            'missing_required_rows' => (int) ($counts['missing_required'] ?? 0),
            'published_rows' => $published,
        ]);
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
