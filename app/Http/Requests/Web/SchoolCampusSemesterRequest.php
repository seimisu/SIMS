<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SchoolCampusSemesterRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'campusId' => ['required', 'exists:school_campuses,id'],
            'semesterId' => ['required', 'exists:list_references,id'],
            'schoolYear' => [
                'required',
                'regex:/^(\d{4})-(\d{4})$/',
                function (string $attribute, mixed $value, \Closure $fail) {
                    [$start, $end] = array_map('intval', explode('-', $value));
                    if ($end !== $start + 1) {
                        $fail('The academic year must contain consecutive years, for example 2026-2027.');
                    }
                },
                Rule::unique('school_campus_semesters', 'school_year')
                    ->where(fn ($query) => $query
                        ->where('campus_id', $this->input('campusId'))
                        ->where('semester_id', $this->input('semesterId'))
                        ->where('is_delete', false)),
            ],
            'submissionDate' => ['required', 'date'],
            'status' => ['required', Rule::in(['draft', 'open'])],
        ];
    }

    public function messages(): array
    {
        return [
            'schoolYear.regex' => 'Use the academic year format YYYY-YYYY, for example 2026-2027.',
        ];
    }

    public function attributes(): array
    {
        return [
            'campusId' => 'campus',
            'semesterId' => 'semester',
            'schoolYear' => 'academic year',
            'submissionDate' => 'submission deadline',
        ];
    }
}
