<?php

return [
    'base_url' => env('EDAAT_BASE_URL', 'https://api-test.edaaat.com'),
    'username' => env('EDAAT_USERNAME', 'alijawish'),
    'password' => env('EDAAT_PASSWORD', 'aJ5181991@'),
    'webhook_url' => env('EDAAT_WEBHOOK_URL', 'https://webhook.site/e61f2b0e-98d8-4e74-affd-729e76c7e493/'),
    'product' => env('EDAAT_PRODUCT', 'lynk'),
    'company' => env('EDAAT_COMPANY', '2020123456'),
    'payment_url' => env('EDAAT_PAYMENT_URL'),
    'bill_url' => env('EDAAT_BILL_URL'),
    'reconcile_url' => env('EDAAT_RECONCILE_URL'),
    'verify_tls_certs' => env('EDAAT_VERIFY_TLS_CERTS', true),
];
