<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;

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
            'channels' => ['single'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        '_db' => [
            'driver' => 'single',
            'path' => storage_path('logs/db/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        '_real_open' => [
            'driver' => 'daily',
            'path' => storage_path('logs/real_open/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],

        '_real_open_err' => [
            'driver' => 'daily',
            'path' => storage_path('logs/real_open_err/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],

        '_push_err' => [
            'driver' => 'daily',
            'path' => storage_path('logs/push_err/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
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
            'handler' => SyslogUdpHandler::class,
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
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

        'Pg_Transfer_Out' => [
            'driver' => 'daily',
            'path' => storage_path('logs/game/pg.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],

        'IMOne_Transfer_Out' => [
            'driver' => 'daily',
            'path' => storage_path('logs/game/IMOne.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],

        'ky_Transfer_Out' => [
            'driver' => 'daily',
            'path' => storage_path('logs/game/ky.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],

        'fpg_Transfer_Out' => [
            'driver' => 'daily',
            'path' => storage_path('logs/game/fpg.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],

        'pg2_Transfer_Out' => [
            'driver' => 'daily',
            'path' => storage_path('logs/game/pg2.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],
    ],

];
