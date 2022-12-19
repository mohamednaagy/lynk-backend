<?php

return [
    'name' => 'Otpify',
    'code_length' => env('OTPIFY_CODE_LENGTH', 4),
    'authorized_token_length' => env('AUTHORIZED_TOKEN_LENGTH', 40),
    'code_expiration_time' => env('OTPIFY_CODE_EXPIRATION_TIME', 10),
    /*
     |--------------------------------------------------------------------------
     | Default Otpify Driver
     |--------------------------------------------------------------------------
     |
     | This option defines the default Otpify driver that gets used when try to
     | send OTP to user via one of implemented drivers. The name specified in this option should match
     | one of the driver defined in the "drivers" configuration array.
     |
     */

    'default' => env('OTPIFY_DEFAULT_DRIVER', 'email'),

    'default_ni_driver' => env('OTPIFY_NATIONAL_ID_DEFAULT_DRIVER', 'absher'),
    /*
     |--------------------------------------------------------------------------
     | Configuration options for each driver
     |--------------------------------------------------------------------------
     |
     | Here you may configure the Otpify drivers for Inspector. Out of
     | the box, Inspector is able to send OTP to user via one of implemented drivers.
     |
     */

    'drivers' => [
        'email' => [
        ],
        'twilio' => [
            'sid' => env('OTPIFY_TWILIO_SID'),
            'token' => env('OTPIFY_TWILIO_TOKEN'),
            'from_phone' => env('OTPIFY_TWILIO_FROM'),
            'verify_sid' => env('OTPIFY_TWILIO_VERIFY_SID', null),
            'ssl_verify_host' => env('OTPIFY_TWILIO_SSL_VERIFY_HOST', false),
            'ssl_verify_peer' => env('OTPIFY_TWILIO_SSL_VERIFY_PEER', false),
        ],
        'absher' => [
            'api_key' => env('ABSHER_API_KEY'),
            'base_url' => env('ABSHER_HOST', 'http://158.101.230.247').'/TCC-Web/api/iam/otp',
        ],
    ],
];
