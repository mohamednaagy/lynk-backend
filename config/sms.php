<?php

return [

    /*
      |--------------------------------------------------------------------------
      | sms_provider
      |--------------------------------------------------------------------------
      |
      | This object has the sms details that constains data we need
      | in the application to use for authentications for SMS service provider
      |
      */
    'msegat' => [
        'url' => 'https://www.msegat.com/gw/sendsms.php',
        'user_name' => env('MSEGAT_USERNAME'),
        'sender_name' => env('MSEGAT_SENDERNAME'),
        'api_key' => env('MSEGAT_API_KEY'),
    ],

];
