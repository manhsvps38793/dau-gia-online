<?php

return [

    'default' => env('BROADCAST_DRIVER', 'socketio'),

    'connections' => [

        // ✅ Kết nối Socket.IO tùy chỉnh
        'socketio' => [
            'driver' => 'custom',
            'via' => App\Broadcasting\SocketIoBroadcaster::class,
            'server' => env('SOCKETIO_SERVER_URL', 'http://127.0.0.1:6001'),
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

];
