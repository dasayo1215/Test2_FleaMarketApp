<?php

use App\Http\Controllers\ItemController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

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

Route::get('/', [ItemController::class, 'index'])->name('index');

Route::get('/register', [RegisterController::class, 'showRegistrationForm']);
Route::post('/register', [RegisterController::class, 'register'])->name('register');

Route::get('/login', [LoginController::class, 'showLoginForm']);
Route::post('/login', [LoginController::class, 'login'])->name('login');

Route::get('/item/{itemId}', [ItemController::class, 'show']);

Route::middleware(['auth', 'verified'])->group(function(){
    Route::get('/purchase/{itemId}', [PurchaseController::class, 'showPurchaseForm'])->name('purchase.show');
    Route::post('/purchase/{itemId}', [PurchaseController::class, 'purchase']);
    Route::post('/purchase/{itemId}/payment-method', [PurchaseController::class, 'savePaymentMethod']);
    Route::get('/purchase/address/{itemId}', [PurchaseController::class, 'showAddressForm']);
    Route::post('/purchase/address/{itemId}', [PurchaseController::class, 'updateAddress']);
    Route::get('/sell', [ItemController::class, 'showSellForm'])->name('sell');
    Route::post('/sell', [ItemController::class, 'store'])->name('store');
    Route::post('/sell/image', [ItemController::class, 'uploadImage']);
    Route::get('/mypage', [UserController::class, 'showProfile'])->name('mypage');
    Route::get('/mypage/profile', [UserController::class, 'editProfile'])->name('profile.edit');
    Route::patch('/mypage/profile', [UserController::class, 'updateProfile']);
    Route::post('/mypage/profile/image', [UserController::class, 'uploadImage']);
    Route::post('/item/{itemId}/comment', [ItemController::class, 'storeComment'])->name('comment');
    Route::post('/item/{itemId}/like', [ItemController::class, 'toggleLike'])->name('like');

    // Stripe関係
    Route::get('/payment-success', [PurchaseController::class, 'success'])->name('payment.success');
    Route::get('/payment-cancel', [PurchaseController::class, 'cancel'])->name('payment.cancel');
});

// メール認証関係
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill(); // 認証完了
    return redirect('/mypage/profile');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', '登録していただいたメールアドレスに認証メールを送付しました。');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

// webhookで商品決済完了を記録
Route::post('/webhook/stripe', [StripeWebhookController::class, 'handle']);