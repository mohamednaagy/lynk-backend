<?php

return [
    'name' => 'Grantify',

    'default_guard' => env('DEFAULT_GUARD', 'web'),

    'guards' => explode(',', env('PERMISSION_GUARDS', 'web,api')),
];
