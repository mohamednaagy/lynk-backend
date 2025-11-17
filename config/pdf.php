<?php

return [
    'default' => env('DEFAULT_PDF_GENERATOR', 'static'),
    'generators' => [
        'static' => [
            'storage_disk' => env('STATIC_PDF_STORAGE_DRIVER', 'local'),
        ],
    ],
];
