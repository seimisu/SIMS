<?php

namespace App\Http\Requests\Web;

use App\Rules\UniqueEntryName;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        switch ($this->route('type')) {
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
                    'name' => ['required', 'string', 'max:255', new UniqueEntryName('programs', $this->route('id'), [
                        'program_id' => $this->input('scholarship.id'),
                        'type_id' => $this->input('type.id'),
                    ])],
                    'description' => ['nullable', 'string', 'max:200'],
                    'scholarship' => ['required', 'array'],
                    'type' => ['required', 'array'],
                    'isSub' => ['boolean'],
                ];
                break;
        }
    }
}
