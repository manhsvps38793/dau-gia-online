<?php

use Illuminate\Support\Facades\Route;
use App\Events\TestEvent;

Route::get('/test-broadcast', function () {
    broadcast(new TestEvent('Xin chào từ Laravel!'));
    return 'Đã gửi event tới Node.js!';
});

