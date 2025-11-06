<?php

namespace App\Broadcasting;

use Illuminate\Contracts\Broadcasting\Broadcaster as BroadcasterContract;
use Illuminate\Support\Facades\Http;
  use Illuminate\Support\Facades\Log;

class SocketIoBroadcaster implements BroadcasterContract
{
    public function auth($request)
    {
        return true;
    }

    public function validAuthenticationResponse($request, $result)
    {
        return true;
    }


public function broadcast(array $channels, $event, array $payload = [])
{
    $server = config('broadcasting.connections.socketio.server') 
              ?? env('SOCKETIO_SERVER_URL', 'http://127.0.0.1:6001');

    foreach ($channels as $channel) {
        try {
            // chuẩn hoá tên channel nếu $channel là object Channel
            $channelName = is_object($channel) && property_exists($channel, 'name')
                ? $channel->name
                : $channel;

            Http::post(rtrim($server, '/') . '/broadcast', [
                'channel' => $channelName,
                'event'   => $event,
                'data'    => $payload,
            ]);
        } catch (\Throwable $e) {
            // log lỗi để debug
            Log::error('SocketIoBroadcaster broadcast failed', [
                'server' => $server,
                'channel' => $channel,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

}
