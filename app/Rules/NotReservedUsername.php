<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class NotReservedUsername implements ValidationRule
{
    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        /** @var list<string> $reserved */
        $reserved = config('auth.reserved_usernames', []);

        if (in_array(strtolower($value), $reserved, true)) {
            $fail('This username is reserved.');
        }
    }
}
