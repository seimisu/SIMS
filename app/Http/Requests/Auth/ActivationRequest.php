<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Override;

class ActivationRequest extends FormRequest
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
            'photo'         => ['nullable', 'string'],
            'photoCrop'     => ['nullable', 'string'],
            'photoRaw'      => ['nullable'],
            'fname'         => ['required', 'string'],
            'lname'         => ['required', 'string'],
            'email'         => ['email', 'required'],
            'school'        => ['required_if:is_coordinator,1'],
            'designation'   => ['required', 'string'],
            'agency'        => ['required', 'array'],
            'contact'       => ['required'],
            'password'      => ['required', Password::min(6)->mixedCase()->numbers()->symbols()],
            'cpassword'     => ['same:password']
        ];
    }

    public function attributes()
    {
        return [
            'cpassword' => 'Confirm password'
        ];
    }

    #[Override]
    public function messages()
    {
        return [
            'school.required_if' => 'Assigned school is required.',
        ];
    }
}
