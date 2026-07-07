<?php

namespace App\Http\Requests\Web;


use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
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
        switch ($this->type) {
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
                    'fname'     => ['required', 'string', 'max:50'],
                    'lname'     => ['required', 'string', 'max:50'],
                    'email'     => ['required', 'email', Rule::unique('users', 'email')->ignore($this->route('id'))],
                    'role'      => ['required', 'array'],
                ];
                break;
        }
    }
    public function attributes(): array
    {
        return [
            'fname'         => 'first name',
            'lname'         => 'last name',

        ];
    }
}
