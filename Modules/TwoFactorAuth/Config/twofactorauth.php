<?php


return [
    /*
    |--------------------------------------------------------------------------
    | Default Two-Factor Auth Driver
    |--------------------------------------------------------------------------
    |
    | This option defines the default Two-Factor Auth driver that gets used when try to
    | send OTP to user via one of implemented drivers. The name specified in this option should match
    | one of the driver defined in the "drivers" configuration array.
    |
    */

    'default' => env('TWOFACTORAUTH_DRIVER', 'email'),

    /*
    |--------------------------------------------------------------------------
    | Configuration options for each driver
    |--------------------------------------------------------------------------
    |
    | Here you may configure the Two-Factor Auth drivers for Inspector. Out of
    | the box, Inspector is able to send OPT to user via one of implemented drivers.
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
