<?php

use App\Enums\FinancingOrderProceedCase;
use App\Enums\TraderOrderMode;
use BenSampo\Enum\Rules\EnumValue;

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
            'modes' => [
                'v1' => [
                    TraderOrderMode::Manual,
                ],
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
            'modes' => [
                'v1' => [
                    TraderOrderMode::Manual,
                    TraderOrderMode::Automatic,
                ],
            ],
        ],
        'bursam' => [
            'latest' => 'v2',
            'fake' => env('BURSAM_FAKE', false),
            'rate_limit' => [
                'decay_seconds' => env('BURSAM_RATE_LIMIT_DECAY_SECONDS', 1),
                'max_attempts' => env('BURSAM_RATE_LIMIT_MAX_ATTEMPTS', 1),
                'max_retries_before_exception' => env('BURSAM_RATE_LIMIT_MAX_RETRIES_BEFORE_EXCEPTION', 1),
            ],
            'base_url' => env('BURSAM_BASE_URL', 'https://traderdcthh-erfmbxcc1323421.uselynk.com'),
            'verify_tls' => env('BURSAM_VERIFY_TLS', false),
            'member_short_name' => env('BURSAM_MEMBER_SHORT_NAME', 'LYNK'),
            'client_secret_key' => env('BURSAM_CLIENT_SECRET_KEY', 'B347B6AFEA16EFA062B6DA'),
            'grant_type' => env('BURSAM_GRANT_TYPE', 'client_credentials'),
            'tenor' => env('BURSAM_TENOR', '00090'),
            'purchasing_commodity_job_backoff_time' => env('BURSAM_PURCHASING_COMMODITY_JOB_BACKOFF_TIME', 10),
            'modes' => [
                'v1' => [
                    TraderOrderMode::Manual,
                ],
                'v2' => [
                    TraderOrderMode::Automatic,
                    TraderOrderMode::Manual,
                ],
            ],
            'allowed_financing_status_to_change_from_public_api' => new EnumValue(FinancingOrderProceedCase::class),
        ],
        'lynk' => [
            'latest' => 'v1',
            'fake' => env('LYNK_LOCAL_COMMODITY_MARKET_FAKE', false),
            'rate_limit' => [
                'decay_seconds' => env('LYNK_LOCAL_COMMODITY_MARKET_LIMIT_DECAY_SECONDS', 1),
                'max_attempts' => env('LYNK_LOCAL_COMMODITY_MARKET_LIMIT_MAX_ATTEMPTS', 1),
                'max_retries_before_exception' => env('LYNK_LOCAL_COMMODITY_MARKET_RATE_LIMIT_MAX_RETRIES_BEFORE_EXCEPTION', 1),
            ],
            'base_url' => env('LYNK_LOCAL_COMMODITY_MARKET_BASE_URL', 'traderdcthh-erfmbxcc1323421.uselynk.com'),
            'verify_tls' => env('LYNK_LOCAL_COMMODITY_MARKET_VERIFY_TLS', false),
            'member_short_name' => env('LYNK_LOCAL_COMMODITY_MARKET_MEMBER_SHORT_NAME', 'LYNK'),
            'client_secret_key' => env('LYNK_LOCAL_COMMODITY_MARKET_CLIENT_SECRET_KEY', 'B347B6AFEA16EFA062B6DA'),
            'grant_type' => env('LYNK_LOCAL_COMMODITY_MARKET_GRANT_TYPE', 'client_credentials'),
            'tenor' => env('LYNK_LOCAL_COMMODITY_MARKET_TENOR', '00090'),
            'purchasing_commodity_job_backoff_time' => env('LYNK_LOCAL_COMMODITY_MARKET_PURCHASING_COMMODITY_JOB_BACKOFF_TIME', 10),
            'modes' => [
                'v1' => [
                    TraderOrderMode::Automatic,
                    TraderOrderMode::Manual,
                ],
            ],
            'max_units_per_trader' => env('LYNK_MAX_UNITS_PER_TRADER', 10000),
        ],
    ],
];
