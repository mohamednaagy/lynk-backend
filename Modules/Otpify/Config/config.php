<?php

return [
    'name' => 'Otpify',

    'code_length' => env('OTPIFY_CODE_LENGTH', 4),

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

    'default' => env('OPTIFY_Default_DRIVER', 'email'),

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
            'sid' => env('TWILIO_SID'),
            'token' => env('TWILIO_TOKEN'),
            'from_phone' => env('TWILIO_FROM')
        ]
    ]
];
