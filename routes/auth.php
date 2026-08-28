<?php

use App\Http\Controllers\Auth\AdminAuthenticatedSessionController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\PhoneLoginController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Auth\WechatLoginController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // ---- 手机号验证码登录 ----
    Route::post('login/phone/send-code', [PhoneLoginController::class, 'sendCode'])
        ->name('login.phone.send-code');
    Route::post('login/phone', [PhoneLoginController::class, 'login'])
        ->name('login.phone');

    // ---- 微信扫码登录 ----
    Route::get('login/wechat/qr', [WechatLoginController::class, 'qr'])
        ->name('login.wechat.qr');
    Route::get('login/wechat/qr/{scene}/status', [WechatLoginController::class, 'status'])
        ->name('login.wechat.status');
    Route::post('login/wechat/qr/{scene}/mock', [WechatLoginController::class, 'mock'])
        ->name('login.wechat.mock');
    Route::get('login/wechat/callback', [WechatLoginController::class, 'callback'])
        ->name('login.wechat.callback');
    Route::get('login/wechat/bind/{scene}', [WechatLoginController::class, 'bind'])
        ->name('login.wechat.bind');
    Route::post('login/wechat/bind/{scene}', [WechatLoginController::class, 'bindStore'])
        ->name('login.wechat.bind-store');

    // ---- 管理端登录（仅客服/管理员；前缀可配置，避免 /admin）----
    Route::get(config('app.admin_url').'/login', [AdminAuthenticatedSessionController::class, 'create'])
        ->name('admin.login');
    Route::post(config('app.admin_url').'/login', [AdminAuthenticatedSessionController::class, 'store'])
        ->name('admin.login.store');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
