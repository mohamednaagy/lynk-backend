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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'bursam' => [
        'timezone' => env('BURSAM_TIME_ZONE', 'Asia/Riyadh'),
        'market_opening_start_time' => env('BURSAM_MARKET_OPENING_START_TIME', '19:30:00'),
        'market_opening_end_time' => env('BURSAM_MARKET_OPENING_END_TIME', '18:30:00'),
        'friday_break_start_time' => env('BURSAM_FRIDAY_BREAK_START_TIME', '08:15:00'),
        'friday_break_end_time' => env('BURSAM_FRIDAY_BREAK_END_TIME', '08:45:00'),
    ],

];
