<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SOAP Code Generation directory
    |--------------------------------------------------------------------------
    |
    | Define the destination for the code generator under the app directory
    */

    'code' => [
        'path' => app_path('Soap'),
        'namespace' => 'App\\Soap',
    ],

    /*
    |--------------------------------------------------------------------------
    | SOAP Ray Configuration
    |--------------------------------------------------------------------------
    |
    | Define if all requests should go to ray
    */

    'ray' => [
        'send_soap_client_requests' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | SOAP Call behaviour
    |--------------------------------------------------------------------------
    |
    | Define if the arguments should be wrapped in an array
    */

    'call' => [
        'wrap_arguments_in_array' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | SOAP Client Configuration
    |--------------------------------------------------------------------------
    |
    | Her you can setup your soap client by configuration so that ou just need
    | a name.
    |
    | example: Soap::buildClient('laravel_soap')
    */

    'clients' => [
        'dmcc' => [
            'with_basic_auth' => [
                'username' => env('DMCC_USERNAME', 'bim.interface.uat'),
                'password' => env('DMCC_PASSWORD', 'Dubai$2030'),
            ],
        ],
    ],

];
