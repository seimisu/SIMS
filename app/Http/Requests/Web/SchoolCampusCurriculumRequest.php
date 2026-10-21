<?php

namespace App\Http\Requests\Web;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class SchoolCampusCurriculumRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        switch ($this->type) {
            case 'delete':
                return [
                    'isDelete' => ['boolean'],
                ];
                break;
            case 'status':
                return [
                    'isActive' => ['boolean'],
                ];
                break;
            default:
                return [
                    'multi' => ['nullable', 'array'],
                    'multi.*.id' => ['nullable'],
                    'multi.*.campus_course_id' => ['nullable'],
                    'multi.*.semesterTypeId' => ['nullable'],
                    'multi.*.yearLevel' => ['required'],
                    'multi.*.elective_limit' => ['nullable', 'integer', 'min:0'],
                    'multi.*.yearNumber' => ['nullable'],
                    'multi.*.subjects' => ['required', 'array'],
                    'multi.*.subjects.*.id' => ['nullable'],
                    'multi.*.subjects.*.name' => ['required', 'string', 'max:200'],
                    'multi.*.subjects.*.semester_array' => ['nullable', 'array'],
                    'multi.*.subjects.*.class_array' => ['required', 'array'],
                    'multi.*.subjects.*.subjectCode' => ['required'],
                    'multi.*.subjects.*.unit' => ['required'],
                    'multi.*.subjects.*.specialization_options' => ['nullable', 'array'],
                    'multi.*.subjects.*.specialization_options.*.id' => ['required', 'integer', 'distinct', 'exists:school_campus_course_specializations,id'],
                    'multi.*.subjects.*.requirement_option' => ['required', 'array'],
                    'multi.*.subjects.*.requirement_option.id' => ['required', 'in:required,elective'],
                    'multi.*.subjects.*.year' => ['nullable'],

                ];
                break;
        }
    }

    public function messages()
    {
        return [
            'multi.*.subjects' => 'Please make sure to add subjects before proceeding.',
            'multi.*.subjects.*.name.required' => 'Please provide the subject name.',
            'multi.*.subjects.*.name.max' => 'The subject description must not exceed 200 characters.',
            'multi.*.subjects.*.class_array' => 'Please select a subject class. ',
            'multi.*.subjects.*.subjectCode' => 'Please enter the subject code.',
            'multi.*.subjects.*.unit' => 'Please specify the number of units.',
            'multi.*.yearLevel' => 'Please enter curriculum year.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            foreach ($this->input('multi', []) as $index => $curriculum) {
                $subjectKeys = [];
                foreach ($curriculum['subjects'] ?? [] as $subjectIndex => $subject) {
                    $code = Str::lower(trim((string) ($subject['subjectCode'] ?? '')));
                    $semesterId = $subject['semester_array']['id'] ?? null;
                    $year = (string) ($subject['year'] ?? '');
                    $key = json_encode([$semesterId, $year, $code]);
                    $field = "multi.{$index}.subjects.{$subjectIndex}.subjectCode";
                    $message = 'This subject code already exists in this curriculum, year level, and semester. Select all applicable specializations on one subject row.';

                    $unchanged = ! empty($curriculum['id']) && ! empty($subject['id'])
                        && DB::table('school_campus_course_curriculum_subjects')
                            ->where('id', $subject['id'])
                            ->where('curriculum_id', $curriculum['id'])
                            ->where('semester_id', $semesterId)
                            ->whereRaw("COALESCE(year::text, '') = ?", [$year])
                            ->whereRaw('LOWER(BTRIM(subject_code)) = ?', [$code])
                            ->where('is_delete', false)
                            ->exists();

                    if (isset($subjectKeys[$key]) && ! ($subjectKeys[$key] && $unchanged)) {
                        $validator->errors()->add($field, $message);

                        continue;
                    }
                    $subjectKeys[$key] = $unchanged;

                    if (! empty($curriculum['id']) && ! $unchanged) {
                        $exists = DB::table('school_campus_course_curriculum_subjects')
                            ->where('curriculum_id', $curriculum['id'])
                            ->where('semester_id', $semesterId)
                            ->whereRaw("COALESCE(year::text, '') = ?", [$year])
                            ->whereRaw('LOWER(BTRIM(subject_code)) = ?', [$code])
                            ->where('is_delete', false)
                            ->when($subject['id'] ?? null, fn ($query, $id) => $query->where('id', '!=', $id))
                            ->exists();
                        if ($exists) {
                            $validator->errors()->add($field, $message);
                        }
                    }
                }
            }

            $seen = [];

            foreach ($this->input('multi', []) as $index => $curriculum) {
                $campusCourseId = $curriculum['campus_course_id'] ?? null;
                $version = trim((string) ($curriculum['yearLevel'] ?? ''));

                if (! $campusCourseId || $version === '') {
                    continue;
                }

                $normalized = Str::lower($version);
                $key = $campusCourseId.'|'.$normalized;

                if (isset($seen[$key])) {
                    $validator->errors()->add(
                        "multi.{$index}.yearLevel",
                        'This curriculum version is already included for the program.'
                    );

                    continue;
                }

                $seen[$key] = true;

                $exists = DB::table('school_campus_course_curriculums')
                    ->where('campus_course_id', $campusCourseId)
                    ->where('is_delete', false)
                    ->whereRaw('LOWER(BTRIM(years)) = ?', [$normalized])
                    ->when(
                        $curriculum['id'] ?? null,
                        fn ($query, $id) => $query->where('id', '!=', $id)
                    )
                    ->exists();

                if ($exists) {
                    $validator->errors()->add(
                        "multi.{$index}.yearLevel",
                        'This curriculum version already exists for the program.'
                    );
                }
            }
        });
    }
}
