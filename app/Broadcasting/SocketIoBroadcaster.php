<?php

namespace App\Broadcasting;

use Illuminate\Contracts\Broadcasting\Broadcaster as BroadcasterContract;
use Illuminate\Support\Facades\Http;

class SocketIoBroadcaster implements BroadcasterContract
{
    public function auth($request)
    {
        // nếu có cần private channel thì xử lý ở đây
        return true;
    }

    public function validAuthenticationResponse($request, $result)
    {
        return true;
    }

    public function broadcast(array $channels, $event, array $payload = [])
    {
        foreach ($channels as $channel) {
            // Gửi dữ liệu sang server Node.js
            Http::post(config('broadcasting.connections.socketio.url') . '/broadcast', [
                'channel' => $channel,
                'event'   => $event,
                'data'    => $payload,
            ]);
        }
    }
}
