<?php

return [
    'base_url' => env('EDAAT_BASE_URL'),
    'username' => env('EDAAT_USERNAME'),
    'password' => env('EDAAT_PASSWORD'),
    'webhook_url' => env('EDAAT_WEBHOOK_URL'),
    'product' => env('EDAAT_PRODUCT'),
    'company' => env('EDAAT_COMPANY'),
    'payment_url' => env('EDAAT_PAYMENT_URL'),
    'bill_url' => env('EDAAT_BILL_URL'),
    'reconcile_url' => env('EDAAT_RECONCILE_URL'),
    'verify_tls_certs' => env('EDAAT_VERIFY_TLS_CERTS', true),
];
