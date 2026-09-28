<?php

namespace App\Services;

use App\Enums\EmailCheckResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Abstract API se check karta hai ke email pe mail aa sakti hai. Sirf sign-up aur email badalne pe,
// har call ka credit lagta hai is liye result 7 din cache hota hai.
// API jawab na de (key nahi, timeout, quota khatam) to CouldNotCheck - kisi ko block nahi karta.
class EmailValidator
{
    private const ENDPOINT = 'https://emailvalidation.abstractapi.com/v1/';

    private const CACHE_DAYS = 7;

    private const TIMEOUT_SECONDS = 4;

    public function check(string $email): EmailCheckResult
    {
        $apiKey = config('services.abstract.email_validation_key');

        if (blank($apiKey)) {
            return EmailCheckResult::CouldNotCheck;
        }

        $cacheKey = 'abstract-email-check:'.sha1(strtolower(trim($email)));

        if ($cachedResult = Cache::get($cacheKey)) {
            return EmailCheckResult::from($cachedResult);
        }

        $result = $this->askAbstract($apiKey, $email);

        // fail call cache nahi karte, agli baar shayad API chal rahi ho
        if ($result !== EmailCheckResult::CouldNotCheck) {
            Cache::put($cacheKey, $result->value, now()->addDays(self::CACHE_DAYS));
        }

        return $result;
    }

    private function askAbstract(string $apiKey, string $email): EmailCheckResult
    {
        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->connectTimeout(self::TIMEOUT_SECONDS)
                ->get(self::ENDPOINT, ['api_key' => $apiKey, 'email' => $email]);
        } catch (ConnectionException $exception) {
            Log::warning('Abstract email check timed out or could not connect, using the basic email check instead.', [
                'error' => $exception->getMessage(),
            ]);

            return EmailCheckResult::CouldNotCheck;
        }

        // 429 = credits khatam, 401 = key galat, 5xx = un ki taraf ka masla
        if ($response->failed()) {
            Log::warning('Abstract email check failed, using the basic email check instead.', [
                'status' => $response->status(),
            ]);

            return EmailCheckResult::CouldNotCheck;
        }

        $deliverability = $response->json('deliverability');

        if (! is_string($deliverability)) {
            Log::warning('Abstract email check sent back an unexpected response, using the basic email check instead.');

            return EmailCheckResult::CouldNotCheck;
        }

        $hasBadFormat = $response->json('is_valid_format.value') === false;
        $isDisposable = $response->json('is_disposable_email.value') === true;

        if ($hasBadFormat || $isDisposable || $deliverability === 'UNDELIVERABLE') {
            return EmailCheckResult::Undeliverable;
        }

        return EmailCheckResult::Deliverable;
    }
}
