<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    AuctionItemController,
    AuthController,
    AuctionProfileController,
    BidsController,
    AuctionSessionController,
    ContractController,
    PaymentController,
    ReportController,
    NotificationController,
    CategoryController,
    DepositPaymentController
};

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// =======================
// 🟢 PUBLIC ROUTES (Ai cũng xem được)
// =======================
Route::get('/products', [AuctionItemController::class, 'index']);
Route::get('/auction-items/{id}', [AuctionItemController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);
Route::get('/auction-sessions', [AuctionSessionController::class, 'index']);
Route::get('/auction-sessions/{id}', [AuctionSessionController::class, 'show']);
Route::get('/bids/{sessionId}', [BidsController::class, 'listBids']);
Route::get('/contracts', [ContractController::class, 'index']);
Route::get('/contracts/{id}', [ContractController::class, 'show']);
Route::get('/payment/return', [PaymentController::class, 'vnpayReturn']);
Route::get('/verify-email/{token}', [AuthController::class, 'verifyEmail']);

// =======================
// 🟡 AUTHENTICATION
// =======================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');
Route::put('/user/update', [AuthController::class, 'update'])->middleware('auth:sanctum');
Route::get('/showuser', [AuthController::class, 'index']);

// =======================
// 🧩 QUẢN LÝ DANH MỤC (Admin + Đấu giá viên)
// =======================
Route::middleware(['auth:sanctum', 'role:Administrator,DauGiaVien'])->group(function () {
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
});

Route::middleware(['auth:sanctum', 'role:Administrator'])->group(function () {
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
});

// =======================
// 🧱 QUẢN LÝ TÀI SẢN (Admin + Đấu giá viên)
// =======================
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/auction-items', [AuctionItemController::class, 'store'])
        ->middleware('role:Administrator,DauGiaVien,ToChucDauGia');
    Route::put('/auction-items/{id}', [AuctionItemController::class, 'update'])
        ->middleware('role:Administrator,DauGiaVien');
    Route::delete('/auction-items/{id}', [AuctionItemController::class, 'destroy'])
        ->middleware('role:Administrator,DauGiaVien');
});

// =======================
// 📑 HỒ SƠ ĐẤU GIÁ (Người dùng, Chuyên viên TTC duyệt)
// =======================
Route::post('/auction-profiles', [AuctionProfileController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:User']);

Route::get('/auction-profiles', [AuctionProfileController::class, 'index'])
    ->middleware(['auth:sanctum']);

Route::put('/auction-profiles/{id}/status', [AuctionProfileController::class, 'updateStatus'])
    ->middleware(['auth:sanctum', 'role:ChuyenVienTTC']);

// =======================
// 💰 TIỀN ĐẶT CỌC (Người dùng nộp, Admin & TTC xử lý)
// =======================
Route::prefix('deposit')->group(function () {
    Route::post('/pay', [DepositPaymentController::class, 'pay'])
        ->middleware(['auth:sanctum', 'role:User']);
    Route::get('/vnpay-return', [DepositPaymentController::class, 'vnpayReturn'])->name('deposit.vnpay.return');
    Route::post('/refund', [DepositPaymentController::class, 'refund'])
        ->middleware(['auth:sanctum', 'role:Administrator,ChuyenVienTTC']);
    Route::get('/status/{profile_id}', [DepositPaymentController::class, 'status'])
        ->middleware(['auth:sanctum']);
});

// =======================
// 🕓 PHIÊN ĐẤU GIÁ (Đấu giá viên & Tổ chức đấu giá)
// =======================
Route::middleware(['auth:sanctum', 'role:DauGiaVien,ToChucDauGia'])->group(function () {
    Route::post('/auction-sessions', [AuctionSessionController::class, 'store']);
    Route::put('/auction-sessions/{id}', [AuctionSessionController::class, 'update']);
    Route::delete('/auction-sessions/{id}', [AuctionSessionController::class, 'destroy']);
});

// =======================
// 💸 LƯỢT TRẢ GIÁ (Người dùng tham gia đấu giá)
// =======================
Route::post('/bids', [BidsController::class, 'placeBid'])
    ->middleware(['auth:sanctum', 'role:User']);

// =======================
// 📜 HỢP ĐỒNG & THANH TOÁN
// =======================

// Thanh toán nội bộ
Route::post('/contracts/{contract_id}/pay', [PaymentController::class, 'makePayment'])
    ->middleware(['auth:sanctum', 'role:User']);

// Thanh toán online qua VNPAY
Route::post('/contracts/{contract_id}/pay-online', [PaymentController::class, 'payOnline'])
    ->middleware(['auth:sanctum', 'role:User']);

// Danh sách & chi tiết thanh toán
Route::get('/payments', [PaymentController::class, 'listPayments'])
    ->middleware(['auth:sanctum']);

// =======================
// 📊 BÁO CÁO (Admin, Chuyên viên TTC)
// =======================
Route::middleware(['auth:sanctum', 'role:Administrator'])->group(function () {
    Route::post('/reports/generate', [ReportController::class, 'generateReport']);
});

Route::middleware(['auth:sanctum', 'role:Administrator,ChuyenVienTTC'])->group(function () {
    Route::get('/reports', [ReportController::class, 'listReports']);
});

// =======================
// 🔔 THÔNG BÁO (Tất cả user có thể đọc, Admin/DGV tạo)
// =======================
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notifications/{user_id}', [NotificationController::class, 'getUserNotifications']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/user/{user_id}/read-all', [NotificationController::class, 'markAllAsRead']);
});

Route::post('/notifications', [NotificationController::class, 'createNotification'])
    ->middleware(['auth:sanctum', 'role:Administrator,DauGiaVien,ChuyenVienTTC']);
