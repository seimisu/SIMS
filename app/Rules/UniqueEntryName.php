<?php

namespace App\Rules;

use App\Support\NamedEntryUniqueness;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueEntryName implements ValidationRule
{
    public function __construct(
        private string $area,
        private int|string|null $ignoreId = null,
        private array $context = [],
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }
        foreach (NamedEntryUniqueness::DEFINITIONS[$this->area]['scope'] as $column => $type) {
            $scope = $this->context[$column] ?? null;
            if ($scope !== null && (! is_scalar($scope) || ($type !== 'text' && filter_var($scope, FILTER_VALIDATE_INT) === false))) {
                $fail('Please select valid options for this entry.');

                return;
            }
        }
        // Existing conflicts may remain, but an unchanged identity does not create a new one.
        if ($this->ignoreId !== null && NamedEntryUniqueness::query($this->area, $value, $this->context)->where('id', $this->ignoreId)->exists()) {
            return;
        }
        if (NamedEntryUniqueness::query($this->area, $value, $this->context, $this->ignoreId)->exists()) {
            $fail(str_ends_with($this->area, '_slugs')
                ? 'This slug already exists. Please enter a different slug.'
                : 'This name already exists. Please enter a different name.');
        }
    }
}
