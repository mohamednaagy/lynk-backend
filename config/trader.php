<?php

return [
    'default' => env('DEFAULT_TRADER', 'fake'),
    'providers' => [
        'dmcc' => [
            'latest' => 'v1',
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
            'latest' => 'v1',
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
            'latest' => 'v2',
            'base_url' => env('BURSAM_BASE_URL', 'traderdcthh-erfmbxcc1323421.uselynk.com'),
            'member_short_name' => env('BURSAM_MEMBER_SHORT_NAME', 'LYNK'),
            'client_secret_key' => env('BURSAM_CLIENT_SECRET_KEY', 'B347B6AFEA16EFA062B6DA'),
            'grant_type' => env('BURSAM_GRANT_TYPE', 'client_credentials'),
            'purchasing_commodity_job_backoff_time' => env('BURSAM_PURCHASING_COMMODITY_JOB_BACKOFF_TIME', 1800),
        ],
    ],
];
