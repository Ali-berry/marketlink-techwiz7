<?php

namespace App\Services\Agent;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Groq chat-completions ka chhota wrapper. Do accounts ki keys hain (primary / secondary),
// ek pe rate limit ya outage ho to doosri use hoti hai.
class GroqClient
{
    private const ENDPOINT = 'https://api.groq.com/openai/v1/chat/completions';

    // rate limit aur server ki choti garbar do second mein theek ho jati hai, ek retry kaafi hai.
    // 400 (schema error) yahan retry nahi hota, wo BaseAgentController handle karta hai
    private const RETRYABLE_STATUSES = [429, 500, 502, 503, 504];

    // 401/403 = key hi kharab hai, retry bekaar, seedha secondary key pe jao
    private const AUTH_FAILURE_STATUSES = [401, 403];

    private const RETRY_DELAY_SECONDS = 3;

    /**
     * @param  array<int, array<string, mixed>>  $messages
     * @param  array<int, array<string, mixed>>  $toolSchemas
     * @return array{choices: array<int, array{message: array<string, mixed>}>}
     */
    public function chat(array $messages, array $toolSchemas = []): array
    {
        try {
            return $this->attempt(config('services.groq.api_key_primary'), $messages, $toolSchemas);
        } catch (RequestException|ConnectionException $exception) {
            if (! $this->isWorthSwitchingKeys($exception)) {
                throw $exception;
            }

            // pata rahe primary account kitni baar rate limit ho raha hai
            Log::warning('Groq primary API key failed, switching to secondary key.', [
                'reason' => $exception->getMessage(),
            ]);

            return $this->attempt(config('services.groq.api_key_secondary'), $messages, $toolSchemas);
        }
    }

    // pehle isi key pe ek retry, phir upar wala decide karta hai key badalni hai ya nahi
    private function attempt(?string $apiKey, array $messages, array $toolSchemas): array
    {
        try {
            return $this->send($apiKey, $messages, $toolSchemas);
        } catch (RequestException $exception) {
            if (! in_array($exception->response->status(), self::RETRYABLE_STATUSES, true)) {
                throw $exception;
            }

            sleep(self::RETRY_DELAY_SECONDS);

            return $this->send($apiKey, $messages, $toolSchemas);
        }
    }

    private function send(?string $apiKey, array $messages, array $toolSchemas): array
    {
        $response = Http::withToken($apiKey)
            ->timeout(30)
            ->post(self::ENDPOINT, array_filter([
                'model' => config('services.groq.model'),
                'messages' => $messages,
                'tools' => $toolSchemas ?: null,
                'tool_choice' => $toolSchemas ? 'auto' : null,
            ]));

        // throw() se 4xx/5xx exception ban jata hai taake caller fallback kar sake
        $response->throw();

        return $response->json();
    }

    // connection toot jaye to doosri key try karo. HTTP error pe sirf rate limit, server error ya
    // auth failure pe - baqi (400, schema) waise hi upar BaseAgentController tak jayen
    private function isWorthSwitchingKeys(RequestException|ConnectionException $exception): bool
    {
        if ($exception instanceof ConnectionException) {
            return true;
        }

        $status = $exception->response->status();

        return in_array($status, self::RETRYABLE_STATUSES, true)
            || in_array($status, self::AUTH_FAILURE_STATUSES, true);
    }
}
