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

    'vehicle' => [
        'url' => env('VEHICLE_API_URL'),
        'token' => env('VEHICLE_API_TOKEN'),
    ],

    'barcode' => [
        'base_url' => env('BARCODE_BASE_URL', 'https://fixnikah-mira-aldi.keluargamahaya.com'),
    ],

    'photobooth' => [
        'verify_url' => env('PHOTOBOOTH_VERIFY_URL', 'https://fixnikah.miraaldi.my.id/moment/verify'),
    ],

];
