<?php

return [
    'default' => env('DEFAULT_TRADER', 'fake'),
    'providers' => [
        'dmcc' => [
            'username' => env('DMCC_USERNAME', 'bim.interface.uat'),
            'password' => env('DMCC_PASSWORD', 'Dubai$2030'),
            'tti' => [
                'payment_terms' => env('DMCC_TTI_PAYMENT_TERMS', '21'),
                'unit_of_duration' => env('DMCC_TTI_UNIT_OF_DURATION', 'Days'),
                'product' => env('DMCC_TTI_PRODUCT', 'rice'),
                'registered_member' => env('DMCC_TTI_REGISTERED_MEMBER', 'BOLFT'),
            ],
        ],
        'fake' => [
            'url' => env('FAKE_TRADER_URL', 'faker-dd.uselynk.com/api/'),
            'username' => env('FAKE_TRADER_USERNAME', 'lynk'),
            'password' => env('FAKE_TRADER_PASSWORD', '12345678'),
            'tti' => [
                'payment_terms' => env('FAKE_TRADER_TTI_PAYMENT_TERMS', '21'),
                'unit_of_duration' => env('FAKE_TRADER_TTI_UNIT_OF_DURATION', 'Days'),
                'product' => env('FAKE_TRADER_TTI_PRODUCT', 'rice'),
                'registered_member' => env('FAKE_TRADER_TTI_REGISTERED_MEMBER', 'BOLFT'),
            ],
        ],

        'bursam' => [
            'client_id' => env('BURSAM_CLIENT_ID'),
            'secret_code' => env('BURSAM_SECRET_CODE'),
            'response_type' => env('BURSAM_RESPONSE_TYPE'),
            'redirect_uri' => env('BURSAM_REDIRECT_URI'),
        ],
    ],
];
