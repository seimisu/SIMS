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
                    'contact' => ['required', 'string'],
                    'email' => ['required', 'email'],
                ];
                break;
        }
    }
}
