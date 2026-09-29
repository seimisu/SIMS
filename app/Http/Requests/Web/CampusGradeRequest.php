<?php

namespace App\Http\Requests\Web;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CampusGradeRequest extends FormRequest
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
                    'campusId' => ['required', 'integer', 'exists:school_campuses,id'],
                    'grade' => ['required', 'string'],
                    'upper' => ['nullable', 'numeric', 'decimal:0,2'],
                    'lower' => ['nullable', 'numeric', 'decimal:0,2'],
                    'fail' => ['boolean'],
                    'drop' => ['boolean'],
                    'incomplete' => ['boolean'],
                    'withdrawn' => ['boolean'],
                ];
                break;
        }
    }
}
