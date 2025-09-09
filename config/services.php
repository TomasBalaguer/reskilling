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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'google' => [
        'project_id' => env('GOOGLE_PROJECT_ID'),
        'credentials_path' => env('GOOGLE_APPLICATION_CREDENTIALS'),
        'api_key' => env('GOOGLE_API_KEY'),
        'location' => env('GOOGLE_LOCATION', 'us-central1'),
        'model' => env('GOOGLE_MODEL', 'gemini-1.5-flash'),
        'vertex_enabled' => env('GOOGLE_VERTEX_ENABLED', false),
        'min_examples_for_finetuning' => env('GOOGLE_MIN_EXAMPLES_FOR_FINETUNING', 50),
        'storage_bucket' => env('GOOGLE_STORAGE_BUCKET'),
        'publisher' => env('GOOGLE_PUBLISHER', 'google'),
        'endpoint_id' => env('GOOGLE_ENDPOINT_ID'),
    ],

];
