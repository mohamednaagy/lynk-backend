<?php

return [
    'name' => 'Grantify',

    'default_guard' => env('DEFAULT_GUARD', 'api'),

    'guards' => explode(',', env('PERMISSION_GUARDS', 'api,web')),
];
