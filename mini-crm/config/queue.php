<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Conexão Padrão de Fila
    |--------------------------------------------------------------------------
    |
    | Configurado via .env como QUEUE_CONNECTION.
    | Em produção: 'redis' | Em testes: 'sync'
    |
    */

    'default' => env('QUEUE_CONNECTION', 'redis'),

    'connections' => [

        // Fila síncrona: executa o Job imediatamente na mesma requisição (uso em testes)
        'sync' => [
            'driver' => 'sync',
        ],

        // Fila baseada em banco de dados (alternativa ao Redis para desenvolvimento simples)
        'database' => [
            'driver'       => 'database',
            'connection'   => env('DB_QUEUE_CONNECTION'),
            'table'        => env('DB_QUEUE_TABLE', 'jobs'),
            'queue'        => env('DB_QUEUE', 'default'),
            'retry_after'  => (int) env('DB_QUEUE_RETRY_AFTER', 90),
            'after_commit' => false,
        ],

        // Fila Redis: usada em produção e desenvolvimento com Docker
        'redis' => [
            'driver'       => 'redis',
            'connection'   => env('REDIS_QUEUE_CONNECTION', 'default'),
            'queue'        => env('REDIS_QUEUE', 'default'),
            'retry_after'  => (int) env('REDIS_QUEUE_RETRY_AFTER', 90),
            'block_for'    => null,
            'after_commit' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Fila de Jobs Falhos
    |--------------------------------------------------------------------------
    |
    | Jobs que excedem o número de tentativas são movidos para a tabela
    | 'failed_jobs' para inspeção e reprocessamento manual.
    |
    */

    'failed' => [
        'driver'   => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'mysql'),
        'table'    => 'failed_jobs',
    ],

];
