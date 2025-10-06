<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuctionItemController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AuctionProfileController;
use App\Http\Controllers\Api\BidsController;
use App\Http\Controllers\Api\AuctionSessionController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\NotificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// 📌 Public routes (ai cũng xem được)
Route::get('/products', [AuctionItemController::class, 'index']);
Route::get('/auction-items/{id}', [AuctionItemController::class, 'show']);

// 📌 Quản lý sản phẩm (chỉ tổ chức, admin)
Route::post('/auction-items', [AuctionItemController::class, 'store'])
    ->middleware('auth:sanctum');
Route::put('/auction-items/{id}', [AuctionItemController::class, 'update'])
    ->middleware('auth:sanctum');
Route::delete('/auction-items/{id}', [AuctionItemController::class, 'destroy'])
    ->middleware('auth:sanctum');

// 📌 Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');
// Sửa thông tin user
Route::put('/user/update', [AuthController::class, 'update'])->middleware('auth:sanctum');

// 📌 Người dùng nộp hồ sơ
Route::post('/auction-profiles', [AuctionProfileController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:User,Customer']);
// 📌 Lấy danh sách hồ sơ 
Route::get('/auction-profiles', [AuctionProfileController::class, 'index']);
// 📌 Chuyên viên TTC duyệt hồ sơ
Route::put('/auction-profiles/{id}/status', [AuctionProfileController::class, 'updateStatus']);
    // ->middleware(['auth:sanctum', 'role:ChuyenVienTTC']); test nhớ mở ra

// 📌 Đấu giá viên tạo phiên
Route::get('/auction-sessions', [AuctionSessionController::class, 'index']);

Route::get('/auction-sessions/{id}', [AuctionSessionController::class, 'show']);


Route::post('/auction-sessions', [AuctionSessionController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:DauGiaVien']);

Route::put('/auction-sessions/{id}', [AuctionSessionController::class, 'update'])
    ->middleware(['auth:sanctum', 'role:DauGiaVien']);

Route::delete('/auction-sessions/{id}', [AuctionSessionController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'role:DauGiaVien']);

// 📌 Người dùng tham gia đặt giá
Route::post('/bids', [BidsController::class, 'placeBid'])
    ->middleware(['auth:sanctum', 'role:User,Customer']);
// show bids
Route::get('/bids/{sessionId}', [BidsController::class, 'listBids']);

// 📌 Đấu giá viên tạo hợp đồng sau phiên
// Route::post('/contracts/{session_id}', [ContractController::class, 'createContract'])
//     ->middleware(['auth:sanctum', 'role:DauGiaVien']); hợp đồng đã tự tạo
    
    Route::get('/contracts', [ContractController::class, 'index']);      // Danh sách hợp đồng
Route::get('/contracts/{id}', [ContractController::class, 'show']); // Chi tiết hợp đồng


// 📌 Thanh toán (người thắng thực hiện)
Route::post('/contracts/{contract_id}/pay', [PaymentController::class, 'makePayment'])
    ->middleware(['auth:sanctum', 'role:User,Customer']);
Route::get('/payments', [PaymentController::class, 'listPayments'])
    ->middleware(['auth:sanctum']);

// 📌 Báo cáo (chỉ admin)
Route::post('/reports/generate', [ReportController::class, 'generateReport'])
    ->middleware(['auth:sanctum', 'role:Administrator']);
Route::get('/reports', [ReportController::class, 'listReports'])
    ->middleware(['auth:sanctum', 'role:Administrator,ChuyenVienTTC']);

// 📌 Thông báo
Route::get('/notifications/{user_id}', [NotificationController::class, 'getUserNotifications'])
    ->middleware('auth:sanctum');
Route::post('/notifications', [NotificationController::class, 'createNotification'])
    ->middleware(['auth:sanctum', 'role:Administrator,DauGiaVien,ChuyenVienTTC']);
Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])
    ->middleware('auth:sanctum');
Route::put('/notifications/user/{user_id}/read-all', [NotificationController::class, 'markAllAsRead'])
    ->middleware('auth:sanctum');
