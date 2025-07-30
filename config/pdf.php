<?php

return [
    'default' => env('DEFAULT_PDF_GENERATOR', 'static'),
    'generators' => [
        'browserless' => [
            'base_url' => env('BROWSERLESS_BASE_URL', 'http://localhost:8000'),
            'storage_disk' => env('BROWSERLESS_STORAGE_DRIVER', 'local'),
        ],

        'static' => [
            'storage_disk' => env('STATIC_PDF_STORAGE_DRIVER', 'local'),
        ],
    ],
];
