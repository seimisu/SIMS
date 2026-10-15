<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        return [
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'fname' => ['required', 'string'],
            'lname' => ['required', 'string'],
            'email' => ['email', 'required'],
            'school' => ['nullable', 'array', 'required_if:is_coordinator,1'],
            'school.id' => ['required_with:school', 'nullable', 'integer', Rule::exists('school_campuses', 'id')->where(fn ($query) => $query->where('is_delete', false))],
            'designation' => ['required', 'string'],
            'agency' => ['required', 'array'],
            'contact' => ['required'],
            'password' => ['required', Password::min(6)->mixedCase()->numbers()->symbols()],
            'cpassword' => ['same:password'],
        ];
    }

    public function attributes()
    {
        return [
            'cpassword' => 'Confirm password',
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
