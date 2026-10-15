<?php

namespace App\Http\Requests\Web;

use App\Models\LocationBarangays;
use App\Support\GradingSystem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SchoolRequest extends FormRequest
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
                    'name' => ['required', 'string', Rule::unique('schools')->where(fn ($q) => $q->where('is_delete', false))->ignore($this->id)],
                    'abbreviation' => ['required', 'string'],
                    'class' => ['required', 'array'],
                    'class.id' => ['required', 'integer', Rule::exists('list_references', 'id')->where('classification', 'Class')],
                    'campuses' => ['required', 'array', 'min:1'],
                    'campuses.*.id' => ['nullable', 'integer'],
                    'campuses.*.name' => ['nullable', 'string'],
                    'campuses.*.semester' => ['required', 'array'],
                    'campuses.*.semester.id' => ['required', 'integer', Rule::exists('list_references', 'id')->where('classification', 'Term Type')],
                    'campuses.*.grading' => ['required', 'array'],
                    'campuses.*.grading.id' => [
                        'required',
                        'integer',
                        Rule::exists('list_references', 'id')->where(fn ($query) => $query
                            ->where('classification', 'Grading System')
                            ->whereIn('name', GradingSystem::SUPPORTED)
                            ->where('is_active', true)
                            ->where('is_delete', false)),
                    ],
                    'campuses.*.startDate' => ['nullable', 'date'],
                    'campuses.*.endDate' => ['nullable', 'date'],
                    'campuses.*.agency' => ['required', 'array'],
                    'campuses.*.agency.id' => ['required', 'integer', 'exists:list_agencies,id'],
                    'campuses.*.street' => ['nullable', 'string'],
                    'campuses.*.address' => ['required', 'array'],
                    'campuses.*.address.id' => ['bail', 'required', 'string', function ($attribute, $value, $fail) {
                        $codes = explode('-', $value);
                        if (count($codes) !== 4 || in_array('', $codes, true)) {
                            $fail('Select a complete address from the address suggestions.');

                            return;
                        }

                        [$barangay, $municipality, $province, $region] = $codes;
                        $valid = LocationBarangays::where('code', $barangay)
                            ->where('municipality_code', $municipality)
                            ->where('is_delete', false)
                            ->whereHas('cityCode', fn ($query) => $query
                                ->where('province_code', $province)
                                ->where('is_delete', false)
                                ->whereHas('provinceCode', fn ($query) => $query
                                    ->where('region_code', $region)
                                    ->where('is_delete', false)
                                    ->whereHas('regionCode', fn ($query) => $query->where('is_delete', false))))
                            ->exists();

                        if (! $valid) {
                            $fail('Select a valid address with matching barangay, municipality, province, and region.');
                        }
                    }],
                    'campuses.*.address.name' => ['required', 'string'],
                    'campuses.*.main' => ['required', 'boolean'],
                ];
                break;
        }
    }

    public function messages()
    {
        return [
            'campuses.required' => 'Add at least one campus with complete details and an address.',
            'campuses.min' => 'Add at least one campus with complete details and an address.',
            'campuses.*.semester.id.required' => 'Select an academic term.',
            'campuses.*.semester.id.exists' => 'Select a valid academic term.',
            'campuses.*.grading.id.required' => 'Select a grading system.',
            'campuses.*.grading.id.exists' => 'Select a valid supported grading system.',
            'campuses.*.agency.id.required' => 'Select a regional office.',
            'campuses.*.agency.id.exists' => 'Select a valid regional office.',
            'campuses.*.address.id.required' => 'Select a complete address from the address suggestions.',
            'campuses.*.semester.required' => 'The academic term field is required.',
            'campuses.*.grading.required' => 'The grading field is required.',
            'campuses.*.agency.required' => 'The agency field is required.',
            'campuses.*.street.required' => 'The street field is required.',
            'campuses.*.address.required' => 'The address field is required.',
            'campuses.*.main.boolean' => 'The main field must be true or false.',
        ];
    }
}
