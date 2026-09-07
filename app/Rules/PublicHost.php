<?php

namespace App\Rules;

use App\Exceptions\UnsafeUrlException;
use App\Support\PublicUrl;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class PublicHost implements ValidationRule
{
    /**
     * Ensure the url points to a host on the public internet, so that the
     * server can't be tricked into connecting to internal services.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        try {
            PublicUrl::resolve($value);
        } catch (UnsafeUrlException $exception) {
            $fail($exception->getMessage());
        }
    }
}
