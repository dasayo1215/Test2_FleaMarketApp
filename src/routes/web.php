<?php

use App\Http\Controllers\ItemController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\TradeRoomController;
use App\Http\Controllers\TradeMessageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [ItemController::class, 'listAvailableItems'])->name('index');

Route::get('/register', [RegisterController::class, 'showRegistrationForm']);
Route::post('/register', [RegisterController::class, 'register'])->name('register');

Route::get('/login', [LoginController::class, 'showLoginForm']);
Route::post('/login', [LoginController::class, 'login'])->name('login');

Route::get('/item/{itemId}', [ItemController::class, 'showItem']);

Route::middleware(['auth', 'verified'])->group(function(){
    Route::get('/purchase/{itemId}', [PurchaseController::class, 'showPurchaseForm'])->name('purchase.show');
    Route::post('/purchase/{itemId}', [PurchaseController::class, 'purchase']);
    Route::post('/purchase/{itemId}/payment-method', [PurchaseController::class, 'savePaymentMethod']);
    Route::get('/purchase/address/{itemId}', [PurchaseController::class, 'showAddressForm']);
    Route::post('/purchase/address/{itemId}', [PurchaseController::class, 'updateAddress']);
    Route::get('/sell', [ItemController::class, 'showSellForm'])->name('sell');
    Route::post('/sell', [ItemController::class, 'storeItem'])->name('store');
    Route::post('/sell/image', [ItemController::class, 'uploadItemImage']);
    Route::get('/mypage', [UserController::class, 'showProfile'])->name('mypage');
    Route::get('/mypage/profile', [UserController::class, 'editProfile'])->name('profile.edit');
    Route::patch('/mypage/profile', [UserController::class, 'updateProfile']);
    Route::post('/mypage/profile/image', [UserController::class, 'uploadProfileImage']);
    Route::post('/item/{itemId}/comment', [ItemController::class, 'storeComment'])->name('comment');
    Route::post('/item/{itemId}/like', [ItemController::class, 'toggleLike'])->name('like');

    // 取引ルーム関係
    Route::get('/trades/{roomId}', [TradeRoomController::class, 'show'])->name('trade.rooms.show');
    Route::post('/trade/{roomId}/messages', [TradeMessageController::class, 'store'])->name('trade.messages.store');

    // Stripe関係
    Route::get('/payment-success', [PurchaseController::class, 'showSuccess'])->name('payment.success');
    Route::get('/payment-cancel', [PurchaseController::class, 'handleCancel'])->name('payment.cancel');
});

// メール認証関係
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [EmailVerificationController::class, 'showNotice'])->name('verification.notice');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resendVerificationEmail'])->middleware('throttle:6,1')->name('verification.send');
});
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verifyEmail'])->middleware(['auth', 'signed'])->name('verification.verify');

// webhookで商品決済完了を記録
Route::post('/webhook/stripe', [StripeWebhookController::class, 'handlePayment']);