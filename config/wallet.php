<?php

declare(strict_types=1);

use App\Support\Generator\ReferenceNumber\ReferenceNumberGenerator;

return [
    'database' => [
        'connection' => 'wallet',
    ],

    'reference_number' => [
        'generator' => ReferenceNumberGenerator::class,
    ],
];
