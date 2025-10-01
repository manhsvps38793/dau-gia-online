<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuctionItemController;

Route::get('/', [AuctionItemController::class, 'index']);
Route::get('/auction-items/{id}', [AuctionItemController::class, 'show']);
