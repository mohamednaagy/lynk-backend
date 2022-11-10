<?php

return [
    'default' => env('DEFAULT_TRADER', 'fake_dmcc'),
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
        'fake_dmcc' => [
            'url' => 'faker-dd.uselynk.com/api/',
            'username' => env('FAKE_DMCC_USERNAME', 'lynk'),
            'password' => env('FAKE_DMCC_PASSWORD', '12345678'),
            'tti' => [
                'payment_terms' => env('DMCC_TTI_PAYMENT_TERMS', '21'),
                'unit_of_duration' => env('DMCC_TTI_UNIT_OF_DURATION', 'Days'),
                'product' => env('DMCC_TTI_PRODUCT', 'rice'),
                'registered_member' => env('DMCC_TTI_REGISTERED_MEMBER', 'BOLFT'),
            ],
        ],
    ],
];
