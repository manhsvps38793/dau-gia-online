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
    DepositPaymentController,
    EContractsController,
    NewsController,
    NewsCategoryController,
    RoleController,
    PermissionController,
    UserRoleController,
    AuctionSessionFavoriteController,
};
use App\Http\Middleware\CheckPermission;
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
Route::get('/showbids', [BidsController::class, 'index']);
Route::get('/contracts', [ContractController::class, 'index']);
Route::post('/contracts/{id}', [ContractController::class, 'update']);
Route::get('/contracts/{id}', [ContractController::class, 'show']);
Route::delete('/contracts/{id}', [ContractController::class, 'destroy']);
Route::delete('/econtracts/{id}', [EContractsController::class, 'destroy']);
Route::delete('/auction-sessions/{id}', [AuctionSessionController::class, 'destroy']);
Route::get('/payment/return', [PaymentController::class, 'vnpayReturn']);
Route::get('/verify-email/{token}', [AuthController::class, 'verifyEmail']);
Route::get('/news', [NewsController::class, 'index']);
Route::get('/news/{id}', [NewsController::class, 'show']);
Route::get('/news-categories', [NewsCategoryController::class, 'index']);
Route::get('/news-categories/{id}', [NewsCategoryController::class, 'show']);
Route::get('/auction-items/search', [AuctionItemController::class, 'search']);
// =======================
// 🟡 AUTHENTICATION
// =======================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');
Route::put('/user/update/{id}', [AuthController::class, 'update'])->middleware('auth:sanctum');
Route::get('/showuser', [AuthController::class, 'index']);
Route::put('/user/approve/{id}', [AuthController::class, 'approveUser'])->middleware('auth:sanctum');
Route::put('/user/reject/{id}', [AuthController::class, 'rejectUser'])->middleware('auth:sanctum');

Route::get('/user/check-lock/{id}', [AuthController::class, 'checkLockStatus'])->middleware('auth:sanctum');

Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::get('/verify-reset/{token}', [AuthController::class, 'verifyResetToken']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
// =======================
// 📰 QUẢN LÝ TIN TỨC
// =======================
Route::middleware(['auth:sanctum', CheckPermission::class.':manage_news'])->group(function () {
    Route::post('/news', [NewsController::class, 'store']);
    Route::put('/news/{id}', [NewsController::class, 'update']);
    Route::patch('/news/{id}', [NewsController::class, 'update']);
    Route::delete('/news/{id}', [NewsController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', CheckPermission::class.':manage_news_categories'])->group(function () {
Route::post('/news-categories', [NewsCategoryController::class, 'store']);
    Route::put('/news-categories/{id}', [NewsCategoryController::class, 'update']);
    Route::delete('/news-categories/{id}', [NewsCategoryController::class, 'destroy']);
});

// =======================
// 🧱 QUẢN LÝ DANH MỤC
// =======================
Route::middleware(['auth:sanctum', CheckPermission::class.':manage_categories'])->group(function () {
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
});

// =======================
// 🧱 QUẢN LÝ TÀI SẢN
// =======================
Route::middleware(['auth:sanctum', CheckPermission::class.':manage_auction_items'])->group(function () {
    Route::post('/auction-items', [AuctionItemController::class, 'store']);
    Route::put('/auction-items/{id}', [AuctionItemController::class, 'update']);
    Route::delete('/auction-items/{id}', [AuctionItemController::class, 'destroy']);
});

// Ảnh phụ sản phẩm
Route::middleware(['auth:sanctum', CheckPermission::class.':manage_auction_items'])->group(function () {
    Route::delete('/auction-items/images/{imageId}', [AuctionItemController::class, 'removeImage']);
    Route::put('/auction-items/{itemId}/images/{imageId}/primary', [AuctionItemController::class, 'setPrimaryImage']);
});
    Route::get('/auction-items/{itemId}/images', [AuctionItemController::class, 'images']);

// =======================
// 📑 HỒ SƠ ĐẤU GIÁ
// =======================
Route::middleware(['auth:sanctum', CheckPermission::class.':create_auction_profile'])->post('/auction-profiles', [AuctionProfileController::class, 'store']);
Route::middleware(['auth:sanctum'])->get('/auction-profiles', [AuctionProfileController::class, 'index']);
Route::middleware(['auth:sanctum', CheckPermission::class.':approve_auction_profile'])->put('/auction-profiles/{id}/status', [AuctionProfileController::class, 'updateStatus']);

// =======================
// 💰 TIỀN ĐẶT CỌC
// =======================
Route::prefix('deposit')->middleware('auth:sanctum')->group(function () {
    Route::post('/pay', [DepositPaymentController::class, 'pay'])->middleware(CheckPermission::class.':pay_deposit');
    Route::get('/vnpay-return', [DepositPaymentController::class, 'vnpayReturn'])->name('deposit.vnpay.return');
    Route::post('/refund', [DepositPaymentController::class, 'refund'])->middleware(CheckPermission::class.':refund_deposit');
    Route::get('/status/{profile_id}', [DepositPaymentController::class, 'status']);
});

// =======================
// 🕓 PHIÊN ĐẤU GIÁ
// =======================
Route::middleware(['auth:sanctum', CheckPermission::class.':manage_auction_sessions'])->group(function () {
    Route::post('/auction-sessions', [AuctionSessionController::class, 'store']);
    Route::put('/auction-sessions/{id}', [AuctionSessionController::class, 'update']);
    Route::post('/auction-sessions/{id}/pause', [AuctionSessionController::class, 'pause']);
    Route::post('/auction-sessions/{id}/resume', [AuctionSessionController::class, 'resume']);
    Route::post('/auction-sessions/{sessionId}/kick/{userId}', [AuctionSessionController::class, 'kickUser']);
    Route::patch('/auction-sessions/{id}/confirm-winner', [AuctionSessionController::class, 'confirmWinner']);
    Route::patch('/auction-sessions/{id}/reject-winner', [AuctionSessionController::class, 'rejectWinner']);
});
// Route::patch('/auction-sessions/{id}/reject-winner', [AuctionSessionController::class, 'rejectWinner']);
// =======================
// 💸 LƯỢT TRẢ GIÁ
// =======================
Route::middleware(['auth:sanctum', CheckPermission::class.':place_bid'])->post('/bids', [BidsController::class, 'placeBid']);

// =======================
// 📜 HỢP ĐỒNG & THANH TOÁN
// =======================
Route::middleware(['auth:sanctum', CheckPermission::class.':make_payment'])->post('/contracts/{contract_id}/pay', [PaymentController::class, 'makePayment']);
Route::middleware(['auth:sanctum', CheckPermission::class.':pay_online'])->post('/contracts/{contract_id}/pay-online', [PaymentController::class, 'payOnline']);
Route::middleware(['auth:sanctum'])->get('/payments', [PaymentController::class, 'listPayments']);
 Route::get('/payments/{id}', [PaymentController::class, 'show']);
    Route::put('/payments/{id}', [PaymentController::class, 'update']);
    Route::delete('/payments/{id}', [PaymentController::class, 'destroy']);
    Route::post('/payments', [PaymentController::class, 'store']);

// =======================
// 📊 BÁO CÁO
// =======================
Route::middleware(['auth:sanctum', CheckPermission::class.':generate_reports'])->post('/reports/generate', [ReportController::class, 'generateReport']);
Route::middleware(['auth:sanctum', CheckPermission::class.':view_reports'])->get('/reports', [ReportController::class, 'listReports']);

// =======================
// 🔔 THÔNG BÁO
// =======================
Route::middleware(['auth:sanctum'])->group(function () {
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/user/{user_id}/read-all', [NotificationController::class, 'markAllAsRead']);
});
Route::middleware(['auth:sanctum', CheckPermission::class.':create_notifications'])->post('/notifications', [NotificationController::class, 'createNotification']);
    Route::get('/notifications/{user_id}', [NotificationController::class, 'getUserNotifications']);
    Route::get('/notification', [NotificationController::class, 'getUserNotification']);
 Route::get('notification/{id}', [NotificationController::class, 'show']); // xem chi tiết
    Route::post('notification/', [NotificationController::class, 'store']); // thêm
    Route::put('notification/{id}', [NotificationController::class, 'update']); // sửa
    Route::delete('notification/{id}', [NotificationController::class, 'destroy']); // xóa
// =======================
// 📜 HỢP ĐỒNG ĐIỆN TỬ
// =======================
Route::middleware(['auth:sanctum', CheckPermission::class.':manage_econtracts'])->group(function () {
    Route::get('/econtracts', [EContractsController::class, 'index']);
    Route::get('/econtracts/{id}', [EContractsController::class, 'show']);
    Route::put('/econtracts/{id}', [EContractsController::class, 'update']);
    Route::post('/econtracts', [EContractsController::class, 'store']);
});

Route::middleware(['auth:sanctum','permission:manage_roles'])->group(function () {
    // Role
    Route::get('/roles',[RoleController::class,'index']);
    Route::post('/roles',[RoleController::class,'store']);
    Route::put('/roles/{id}',[RoleController::class,'update']);
    Route::delete('/roles/{id}',[RoleController::class,'destroy']);
    Route::post('/roles/{id}/permissions',[RoleController::class,'assignPermission']);

    // Permission
    Route::get('/permissions',[PermissionController::class,'index']);
    Route::post('/permissions',[PermissionController::class,'store']);
    Route::put('/permissions/{id}',[PermissionController::class,'update']);
    Route::delete('/permissions/{id}',[PermissionController::class,'destroy']);

    // User role
   // routes/api.php
    Route::get('users/{userId}/roles', [UserRoleController::class, 'index']);
    Route::post('users/{userId}/roles', [UserRoleController::class, 'assignRole']);
    Route::delete('users/{userId}/roles', [UserRoleController::class, 'removeRole']);
});
  Route::get('/roles/{id}/permissions', [RoleController::class, 'getPermissions']);


// xuất file thông tin cá nhân của admin
Route::middleware('auth:sanctum', 'permission:manage_users')->group(function () {
    Route::get('/users/export-pdf/{id}', [AuthController::class, 'exportUserPDF']); // xuất 1 người pdf
    Route::get('/users/export-excel', [AuthController::class, 'exportUsersExcel']); // xuất nhiều người excel
    Route::post('/user/lock/{id}', [AuthController::class, 'lockUser'])->middleware('auth:sanctum');
    Route::post('/user/unlock/{id}', [AuthController::class, 'unlockUser'])->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/sessions/{id}/favorite', [AuctionSessionFavoriteController::class, 'toggleFavorite']);
    Route::get('/my-favorites', [AuctionSessionFavoriteController::class, 'myFavorites']);
});

