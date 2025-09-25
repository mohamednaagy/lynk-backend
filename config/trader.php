<?php

use App\Enums\FinancingOrderProceedCase;
use App\Enums\TraderOrderMode;
use App\Settings\Classes\LocalMurabahaSettings;
use BenSampo\Enum\Rules\EnumValue;
use Carbon\Carbon;

return [
    'default' => env('DEFAULT_TRADER', 'fake'),
    'providers' => [
        'dmcc' => [
            'latest' => 'v1',
            'username' => env('DMCC_USERNAME'),
            'password' => env('DMCC_PASSWORD'),
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
            'default_contract_sign_time_limit' => function () {
                return 240;
            },
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
            'default_contract_sign_time_limit' => function () {
                return 240;
            },
        ],
        'bursam' => [
            'latest' => 'v2',
            'fake' => env('BURSAM_FAKE', false),
            'rate_limit' => [
                'decay_seconds' => (int) env('BURSAM_RATE_LIMIT_DECAY_SECONDS', 1),
                'max_attempts' => (int) env('BURSAM_RATE_LIMIT_MAX_ATTEMPTS', 1),
                'max_retries_before_exception' => (int) env('BURSAM_RATE_LIMIT_MAX_RETRIES_BEFORE_EXCEPTION', 1),
            ],
            'base_url' => env('BURSAM_BASE_URL', 'https://traderdcthh-erfmbxcc1323421.uselynk.com'),
            'verify_tls' => env('BURSAM_VERIFY_TLS', false),
            'member_short_name' => env('BURSAM_MEMBER_SHORT_NAME', 'LYNK'),
            'client_secret_key' => env('BURSAM_CLIENT_SECRET_KEY', 'B347B6AFEA16EFA062B6DA'),
            'grant_type' => env('BURSAM_GRANT_TYPE', 'client_credentials'),
            'tenor' => env('BURSAM_TENOR', '00090'),
            'purchasing_commodity_job_backoff_time' => (int) env('BURSAM_PURCHASING_COMMODITY_JOB_BACKOFF_TIME', 10),
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
            'default_contract_sign_time_limit' => function () {
                return Carbon::now()->diffInMinutes(get_bursam_contract_signed_deadline());
            },
        ],
        'lynk' => [
            'latest' => env('LYNK_DEFAULT_VERSION', 'v2'),
            'modes' => [
                'v1' => [
                    TraderOrderMode::Manual,
                    TraderOrderMode::Automatic,
                ],
                'v2' => [
                    TraderOrderMode::Manual,
                    TraderOrderMode::Automatic,
                ],
            ],
            'max_units_per_trader' => (int) env('LYNK_MAX_UNITS_PER_TRADER', 10000),
            'max_count_eligible_units_per_inventory' => (int) env('LYNK_MAX_COUNT_ELIGIBLE_UNITS_PER_INVENTORY', 10000),
            'refresh_inventory_stock_delay' => (int) env('REFRESH_INVENTORY_STOCK_DELAY', 10), // seconds
            'loan_coverage_strategy' => env('LOAN_COVERAGE_STRATEGY', 'optimized'),
            'loan_coverage_timeout' => (int) env('LOAN_COVERAGE_TIMEOUT', 3), // 3sec
            'default_contract_sign_time_limit' => function () {
                return app(LocalMurabahaSettings::class)->default_contract_sign_time_limit * 60;
            },
        ],
    ],
];
