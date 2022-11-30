<?php

return [
    /*
     |--------------------------------------------------------------------------
     | Default Mobile Verify Driver
     |--------------------------------------------------------------------------
     |
     | This option defines the default Mobile Verify driver that gets used when try to
     | verify the user's mobile number via one of implemented drivers.
     | The name specified in this option should match
     | one of the driver defined in the "drivers" configuration array.
     |
     */

    'default' => env('MOBILE_VERIFY_DEFAULT_DRIVER', 'tcc'),

    /*
    |--------------------------------------------------------------------------
    | Configuration options for each driver
    |--------------------------------------------------------------------------
    |
    | Here you may configure the Mobile Verify drivers for Inspector. Out of
    | the box, Inspector is able to verify the user's mobile number via one of implemented drivers.
    |
    */

    'drivers' => [
        'tcc' => [
            'api_key' => env('MOBILE_VERIFY_TCC_API_KEY'),
            'base_url' => env('MOBILE_VERIFY_TCC_BASE_URL', 'http://158.101.230.247'),
        ],
    ],
];
