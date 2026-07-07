<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->type == 'status') {
            return [
                'isActive' => ['boolean']
            ];
        } elseif ($this->type == 'permissions') {
            return [
                'permissions' => ['array'],
                'permissions.*' => ['integer', 'exists:list_permissions,id'],
            ];
        } else {
            return [
                'name' => ['required', Rule::unique('list_roles', 'name')->ignore($this->route('id')), 'string'],
                'slug' => ['required', 'string'],
                'description' => ['required', 'string'],
                'isLock' => ['boolean'],

            ];
        }
    }
}
