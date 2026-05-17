<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;

return [

    /*
    |--------------------------------------------------------------------------
    | Canal de Log Padrão
    |--------------------------------------------------------------------------
    |
    | Definido via .env como LOG_CHANNEL. Em desenvolvimento usamos 'stack'
    | para gravar tanto no laravel.log quanto no contact.log conforme solicitado.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace'   => env('LOG_DEPRECATIONS_TRACE', false),
    ],

    'channels' => [

        // Canal stack: combina múltiplos canais
        'stack' => [
            'driver'            => 'stack',
            'channels'          => explode(',', env('LOG_STACK', 'single')),
            'ignore_exceptions' => false,
        ],

        // Canal padrão do Laravel
        'single' => [
            'driver' => 'single',
            'path'   => storage_path('logs/laravel.log'),
            'level'  => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        /*
        |----------------------------------------------------------------------
        | Canal dedicado para eventos de contatos
        |----------------------------------------------------------------------
        |
        | Requerido pelo desafio: os listeners gravam neste canal após o
        | processamento do score. Gera o arquivo storage/logs/contact.log
        |
        */
        'contact' => [
            'driver' => 'single',
            'path'   => storage_path('logs/contact.log'),
            'level'  => 'info',
            'replace_placeholders' => true,
        ],

        // Canal nulo: usado em testes para suprimir logs
        'null' => [
            'driver'  => 'monolog',
            'handler' => NullHandler::class,
        ],

        'stderr' => [
            'driver'    => 'monolog',
            'level'     => env('LOG_LEVEL', 'debug'),
            'handler'   => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with'      => ['stream' => 'php://stderr'],
        ],
    ],

];
