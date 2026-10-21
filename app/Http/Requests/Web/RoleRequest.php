<?php

namespace App\Http\Requests\Web;

use App\Rules\UniqueEntryName;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RoleRequest extends FormRequest
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
        if ($this->type == 'status') {
            return [
                'isActive' => ['boolean'],
            ];
        } elseif ($this->type == 'permissions') {
            return [
                'permissions' => ['array'],
                'permissions.*' => ['integer', 'exists:list_permissions,id'],
            ];
        } else {
            return [
                'name' => ['required', 'string', new UniqueEntryName('roles', $this->route('id'))],
                'slug' => ['required', 'string', new UniqueEntryName('role_slugs', $this->route('id'))],
                'description' => ['required', 'string', 'max:200'],
                'isLock' => ['boolean'],

            ];
        }
    }
}
