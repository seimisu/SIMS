<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PhilippineMobileNumber implements ValidationRule
{
    // Validate whitespace-only input too, while still allowing an omitted number.
    public bool $implicit = true;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value) || preg_match('/\A09[0-9]{9}\z/', $value) !== 1) {
            $fail('Please enter an 11-digit mobile number starting with 09.');
        }
    }
}
