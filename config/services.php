<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // teeno AI agents ke liye (App\Services\Agent). Do accounts - primary rate limit ya band ho to secondary
    'groq' => [
        'api_key_primary' => env('GROQ_API_KEY_PRIMARY'),
        'api_key_secondary' => env('GROQ_API_KEY_SECONDARY'),
        'model' => env('GROQ_MODEL', 'openai/gpt-oss-120b'),
    ],

    // Abstract Email Validation - sign-up aur email badalne pe check (App\Services\EmailValidator).
    // key na ho to bhi app chalti hai, sirf Laravel ka email:rfc,dns check hota hai
    'abstract' => [
        'email_validation_key' => env('ABSTRACT_EMAIL_VALIDATION_API_KEY'),
    ],

    // maps_key = registration ke Area/Location ka Places autocomplete, key na ho to plain text input.
    // client_id / client_secret = "Continue with Google" (GoogleController), Google Cloud Console se keys
    'google' => [
        'maps_key' => env('GOOGLE_MAPS_API_KEY'),
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

];
