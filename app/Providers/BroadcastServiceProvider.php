<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;
use App\Broadcasting\SocketIoBroadcaster;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        // Đăng ký route cho kênh nếu bạn dùng private channel
        Broadcast::routes();

        // Đăng ký broadcaster custom 'socketio'
        Broadcast::extend('socketio', function ($app, $config) {
            return new SocketIoBroadcaster();
        });
    }
}
