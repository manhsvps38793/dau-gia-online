<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuctionItemController;
use App\Http\Controllers\Api\AuthController;

Route::get('/', [AuctionItemController::class, 'index']);
Route::get('/auction-items/{id}', [AuctionItemController::class, 'show']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');