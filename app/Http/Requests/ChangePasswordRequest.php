<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    protected $dontFlash = [
        'current',
        'new',
        'confirm',
    ];
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
            'current' => ['required'],
            'new'     => ['required', Password::min(6)->mixedCase()->numbers()->symbols()],
            'confirm' => ['required', 'same:new']
        ];
    }

    public function attributes(): array
    {
        return [
            'current' => 'current password',
            'new' => 'new password',
            'confirm' => 'password confirmation',
        ];
    }

    public function withValidator($validator)
    {

        $validator->after(function ($validator) {
            if (! Hash::check($this->input('current'), $this->user()->password)) {
                $validator->errors()->add('current', 'The current password is incorrect.');
            }
        });
    }
}
