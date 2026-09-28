<?php

namespace App\Rules;

use App\Enums\EmailCheckResult;
use App\Services\EmailValidator;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;

// email field pe 'bail' ke saath sab se aakhir mein lagao, taake credit sirf sahi aur naye email pe lage
class ValidDeliverableEmail implements ValidationRule
{
    public const MESSAGE = 'Please enter a real email address you can receive mail at.';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $checkResult = app(EmailValidator::class)->check((string) $value);

        if ($checkResult === EmailCheckResult::Undeliverable) {
            $fail(self::MESSAGE);

            return;
        }

        // API ne jawab nahi diya - phir bhi Laravel ka DNS check galat domain pakar leta hai
        if ($checkResult === EmailCheckResult::CouldNotCheck) {
            $basicCheck = Validator::make(['email' => $value], ['email' => 'email:rfc,dns']);

            if ($basicCheck->fails()) {
                $fail(self::MESSAGE);
            }
        }
    }
}
