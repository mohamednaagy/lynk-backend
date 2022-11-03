<?php

return [
    'default' => env('DEFAULT_TRADER', 'dmcc'),
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
    ],
];
