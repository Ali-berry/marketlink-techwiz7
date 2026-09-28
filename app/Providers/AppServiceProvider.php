<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        // har password field (register, reset, change) ka ek rule - 8+ characters, upper / lower, number aur symbol
        Password::defaults(fn () => Password::min(8)->mixedCase()->numbers()->symbols());

        // har AI message pe Groq call lagti hai, is liye 20 per minute. Reply chat widget wale {reply} shape mein
        RateLimiter::for('agent-chat', function (Request $request) {
            return Limit::perMinute(20)
                ->by($request->user()->id)
                ->response(fn () => response()->json([
                    'reply' => "You're sending messages a little fast. Please wait a minute and try again.",
                ], 429));
        });
    }
}
