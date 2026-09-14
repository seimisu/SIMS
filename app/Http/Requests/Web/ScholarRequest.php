<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class ScholarRequest extends FormRequest
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
            'files' => ['required'],
            'files.*' => 'file|mimes:xls,xlsx,csv',
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'files.*.file' => 'Each item must be a valid file.',
            'files.*.mimes' => 'Invalid Excel format. Only XLS, XLSX, and CSV files are allowed.',
       
        ];
    }
}
