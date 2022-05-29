<?php

return [
    'name' => 'Permission',

    'guards' => explode(',', env('PERMISSION_GUARDS', 'web,api'))
];
