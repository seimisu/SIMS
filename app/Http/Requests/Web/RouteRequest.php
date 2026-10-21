<?php

namespace App\Http\Requests\Web;

use App\Rules\UniqueEntryName;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RouteRequest extends FormRequest
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
        } else {
            return [
                'role' => ['required'],
                'label' => ['required', 'string', new UniqueEntryName('navigation', $this->route('id'), [
                    'main_id' => $this->boolean('isSubmenu') ? $this->input('submenu.id') : null,
                ])],
                'slug' => [
                    'required',
                    'string',
                    new UniqueEntryName('navigation_slugs', $this->route('id')),
                ],
                'icon' => ['required'],
                'route' => ['nullable'],
                'component' => ['nullable'],
                'isSubmenu' => ['boolean'],
                'submenu' => ['required_if:isSubmenu,true'],

            ];
        }
    }

    public function messages()
    {
        return [
            'submenu.required_if' => 'The parent select is required.',
        ];
    }
}
