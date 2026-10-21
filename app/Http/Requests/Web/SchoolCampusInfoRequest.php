<?php

namespace App\Http\Requests\Web;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SchoolCampusInfoRequest extends FormRequest
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
                    'id' => ['nullable'],
                    'campusId' => ['required', Rule::exists('school_campuses', 'id')->where(fn ($query) => $query->where('is_delete', false))],
                    'dean' => ['required', 'string'],
                    'registrar' => ['required', 'string'],
                    'contact' => ['bail', 'required', 'string', 'max:11', 'regex:/\A[0-9]+\z/'],
                    'email' => ['required', 'email'],
                ];
                break;
        }
    }

    public function messages(): array
    {
        return [
            'contact.required' => 'Please enter the school contact number.',
            'contact.string' => 'Please enter a contact number using digits only.',
            'contact.max' => 'The school contact number must not exceed 11 digits.',
            'contact.regex' => 'Please enter a contact number using digits only, without spaces or symbols.',
        ];
    }
}
