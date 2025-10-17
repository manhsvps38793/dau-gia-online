<?php

return [

    'default' => env('BROADCAST_DRIVER', 'socketio'),

    'connections' => [

        'socketio' => [
            'driver' => 'socketio',
            'url' => env('SOCKETIO_SERVER_URL', 'http://127.0.0.1:6001'),
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

];
