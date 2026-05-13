<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Driver de Broadcast Padrão
    |--------------------------------------------------------------------------
    |
    | Configurado via .env como BROADCAST_CONNECTION.
    | Em produção: 'reverb' | Em testes: 'null'
    |
    */

    'default' => env('BROADCAST_CONNECTION', 'null'),

    'connections' => [

        /*
        |----------------------------------------------------------------------
        | Laravel Reverb — WebSocket nativo do Laravel
        |----------------------------------------------------------------------
        |
        | Reverb é o servidor WebSocket oficial do Laravel. O cliente frontend
        | usa o Echo.js para conectar e escutar os eventos.
        |
        | Canal usado: contacts.{id}
        | Evento JS: .ContactScoreUpdated
        |
        */
        'reverb' => [
            'driver'  => 'reverb',
            'key'     => env('REVERB_APP_KEY'),
            'secret'  => env('REVERB_APP_SECRET'),
            'app_id'  => env('REVERB_APP_ID'),
            'options' => [
                'host'   => env('REVERB_HOST'),
                'port'   => env('REVERB_PORT', 443),
                'scheme' => env('REVERB_SCHEME', 'https'),
                'useTLS' => env('REVERB_SCHEME', 'https') === 'https',
            ],
            'client_options' => [],
        ],

        // Driver nulo: descarta todos os broadcasts (usado em testes)
        'null' => [
            'driver' => 'null',
        ],

        // Pusher: mantido por compatibilidade com outros projetos
        'pusher' => [
            'driver'  => 'pusher',
            'key'     => env('PUSHER_APP_KEY'),
            'secret'  => env('PUSHER_APP_SECRET'),
            'app_id'  => env('PUSHER_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER'),
                'host'    => env('PUSHER_HOST') ?: 'api-' . env('PUSHER_APP_CLUSTER', 'mt1') . '.pusher.com',
                'port'    => env('PUSHER_PORT', 443),
                'scheme'  => env('PUSHER_SCHEME', 'https'),
                'useTLS'  => env('PUSHER_SCHEME', 'https') === 'https',
                'curl_options' => [
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_SSL_VERIFYPEER => 0,
                ],
            ],
            'client_options' => [],
        ],

    ],

];
