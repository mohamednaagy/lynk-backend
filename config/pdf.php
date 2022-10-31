<?php

return [
    'default' => env('DEFAULT_PDF_GENERATOR', 'browserless'),
    'generators' => [
        'browserless' => [
            'base_url' => env('BROWSERLESS_BASE_URL', 'http://localhost:8000'),
            'storage_disk' => env('BROWSERLESS_STORAGE_DRIVER', 'local'),
        ],
    ],
];
