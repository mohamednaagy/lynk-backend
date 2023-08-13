<?php

return [

    /*
      |--------------------------------------------------------------------------
      | sms_provider
      |--------------------------------------------------------------------------
      |
      | This object has the sms details that contains data we need
      | in the application to use for authentications for SMS service provider
      |
      */

    'provider' => env('SMS_DEFAULT_DRIVER', 'fake'),
    'logging' => env('SMS_LOGGING', false),
    'msegat' => [
        'url' => 'https://www.msegat.com/gw/sendsms.php',
        'user_name' => env('MSEGAT_USERNAME', ''),
        'sender_name' => env('MSEGAT_SENDERNAME', ''),
        'api_key' => env('MSEGAT_API_KEY', ''),
    ],

];
