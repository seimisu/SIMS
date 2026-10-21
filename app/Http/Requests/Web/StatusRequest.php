<?php

namespace App\Http\Requests\Web;

use App\Rules\UniqueEntryName;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StatusRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('type'))) {
            $this->merge(['type' => Str::lower(trim($this->input('type')))]);
        }
    }

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
                    'name' => ['required', 'string', new UniqueEntryName('statuses', $this->route('id'), ['type' => $this->input('type')])],
                    'type' => ['required', 'string'],
                    'icon' => ['nullable'],
                    'color' => ['required'],
                ];
                break;
        }
    }
}
