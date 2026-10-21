<?php

namespace App\Http\Requests\Web;

use App\Rules\UniqueEntryName;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReferenceRequest extends FormRequest
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
                    'name' => ['required', 'string', new UniqueEntryName('references', $this->route('id'), [
                        'classification' => $this->input('class.id'),
                        'type' => $this->input('typeReference.id'),
                    ])],
                    'class' => ['required'],
                    'typeReference' => ['required'],
                    'color' => ['nullable', 'array'],
                    'others' => ['nullable', 'string'],
                ];
                break;
        }
    }
}
