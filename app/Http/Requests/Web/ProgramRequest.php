<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProgramRequest extends FormRequest
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

        switch ($this->route('type')) {
            case 'delete':
                return [
                    'isDelete' => ['boolean']
                ];
                break;
            case 'status':
                return [
                    'isActive' => ['boolean']
                ];
                break;
            default:
                return [
                    'name' => ['required', Rule::unique('list_programs')->ignore($this->id)->where(function ($query) {
                        return $query->where('is_delete', false);
                    }), 'string', 'max:255'],
                    'description' => ['nullable', 'string', 'max:200'],
                    'scholarship' => ['required', 'array'],
                    'type' => ['required', 'array'],
                    'isSub' => ['boolean'],
                ];
                break;
        }
    }
}
