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

    'whatsapp' => [
        'bot_url' => env('WHATSAPP_BOT_URL', 'http://localhost:3000'),
        'bot_api_key' => env('BOT_API_KEY'),
        'timeout' => env('WHATSAPP_BOT_TIMEOUT', 10),
        'otp_expires_in_minutes' => env('OTP_EXPIRES_IN_MINUTES', 5),
        'otp_rate_limit_max_attempts' => env('OTP_RATE_LIMIT_MAX_ATTEMPTS', 1),
        'otp_rate_limit_decay_seconds' => env('OTP_RATE_LIMIT_DECAY_SECONDS', 60),
    ],

];
