<?php

return [
    'name' => 'Permission',

    'guards' => explode(',', env('GUARDS', 'web'))
];
