<?php

use Illuminate\Support\Facades\Storage;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Symfony\Component\Filesystem\Path;

$formatSettings = [
    "format" => "[%datetime%] %level_name%: %message% %context% %extra%\n",
];

return [
    /**
     * --------------------------------------------------------------------------
     * Default Log Channel
     * --------------------------------------------------------------------------
     *
     * This option defines the default log channel that gets used when writing
     * messages to the logs. The name specified in this option should match
     * one of the channels defined in the "channels" configuration array.
     */
    'default' => env('LOG_CHANNEL', 'stack'),

    /**
     * --------------------------------------------------------------------------
     * Deprecations Log Channel
     * --------------------------------------------------------------------------
     *
     * This option controls the log channel that should be used to log warnings
     * regarding deprecated PHP and library features. This allows you to get
     * your application ready for upcoming major versions of dependencies.
     */
    'deprecations' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),

    /**
     * --------------------------------------------------------------------------
     * Log Channels
     * --------------------------------------------------------------------------
     *
     * Here you may configure the log channels for your application. Out of
     * the box, Laravel uses the Monolog PHP logging library. This gives
     * you a variety of powerful log handlers / formatters to utilize.
     *
     * Available Drivers: "single", "daily", "slack", "syslog",
     *                    "errorlog", "monolog",
     *                    "custom", "stack"
     */
    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => [
                'daily',
                'stderr',
            ],
            'ignore_exceptions' => false,
        ],
        'daily' => [
            'driver' => 'daily',
            'path' => Path::join(getcwd(), 'logs', 'laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
            'formatter' => env('LOG_FORMATTER', LineFormatter::class),
            'formatter_with' => $formatSettings,
        ],
        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_FORMATTER', LineFormatter::class),
            'formatter_with' => $formatSettings,
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],
    ],
];
