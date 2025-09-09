<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;

const LOG_CHANNEL_BURSAM = 'bursam';
const LOG_CHANNEL_LOCAL_MARKET = 'local_market';
const LOG_CHANNEL_LYNK = 'lynk';
const LOG_CHANNEL_AUTO_COMPLETE_SELL = 'bursam_autosell';
const LOG_CHANNEL_COMMODITIES_SETTLEMENT = 'commodities_settlement';
const LOG_CHANNEL_WEBHOOKS = 'webhooks';

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', env('LOG_STACK', 'daily,nightwatch')),
            'ignore_exceptions' => false,
            'tap' => [App\Logging\CustomizeLogTimezone::class],
        ],

        'single' => [
            'driver' => 'daily',
            'path' => storage_path('logs/lynk/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],
        'custom' => [
            'driver' => 'single',
            'path' => storage_path('logs/custom.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],
        'transactionUpdates' => [
            'driver' => 'single',
            'path' => storage_path('logs/transaction-updates.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],
        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/lynk/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 30,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => env('LOG_LEVEL', 'critical'),
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
            ],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

        'nightwatch' => [
            'driver' => 'custom',
            'via' => \Laravel\Nightwatch\Factories\Logger::class,
            'token' => env('NIGHTWATCH_TOKEN'),
            'level' => env('NIGHTWATCH_LOG_LEVEL', 'debug'),
        ],

        LOG_CHANNEL_BURSAM => [
            'driver' => 'daily',
            'path' => storage_path('logs/bursam/bursam.log'),
            'level' => 'debug',
            'days' => 30,
        ],
        LOG_CHANNEL_AUTO_COMPLETE_SELL => [
            'driver' => 'daily',
            'path' => storage_path('logs/bursam/bursam_autosell.log'),
            'level' => 'debug',
            'days' => 30,
        ],
        LOG_CHANNEL_LOCAL_MARKET => [
            'driver' => 'daily',
            'path' => storage_path('logs/local-market/local-market.log'),
            'level' => 'debug',
            'days' => 30,
        ],
        LOG_CHANNEL_LYNK => [
            'driver' => 'daily',
            'path' => storage_path('logs/lynk/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 30,
        ],
        LOG_CHANNEL_COMMODITIES_SETTLEMENT => [
            'driver' => 'daily',
            'path' => storage_path('logs/local-market/commodities-settlement.log'),
            'level' => 'debug',
            'days' => 30,
        ],
        'live_market' => [
            'driver' => 'daily',
            'path' => storage_path('logs/live-market/live-market.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],
        LOG_CHANNEL_WEBHOOKS => [
            'driver' => 'daily',
            'path' => storage_path('logs/webhooks/webhooks.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 30,
        ],
    ],

];
