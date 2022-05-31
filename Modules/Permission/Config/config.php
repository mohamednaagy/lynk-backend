<?php

return [
    'name' => 'Permission',

    'guards' => explode(',', env('PERMISSION_GUARDS', 'web,api')),

    /*
    |--------------------------------------------------------------------------
    | Default Grantify Driver
    |--------------------------------------------------------------------------
    |
    | This option defines the default Grantify driver that gets used when try to
    | access the roles and permissions in the system. The name specified in this option should match
    | one of the driver defined in the "drivers" configuration array.
    |
    */

    'default' => env('GRANTIFY_DEFAULT_DRIVER', 'spatie'),
];
