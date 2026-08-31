<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\KbApiController;
use App\Http\Controllers\Api\NotificationApiController;
use App\Http\Controllers\Api\ReferenceApiController;
use App\Http\Controllers\Api\TicketApiController;
use Illuminate\Support\Facades\Route;

/**
 * 对外 API（App / 小程序 / 第三方）
 *
 * 认证：POST /api/auth/login → access_token
 * 之后所有请求头带 Authorization: Bearer <token>
 *
 * 版本约定：资源路径不含版本号，破坏性变更时再上 /v2
 */

// ---- 公开 ----
// 登录限流：每 IP 每分钟最多 5 次（防爆破；短信验证码另有 60s 重发保护）
Route::post('/auth/login', [AuthApiController::class, 'login'])->middleware('throttle:5,1');

// ---- 需 token ----
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthApiController::class, 'logout']);
    Route::get('/me', [AuthApiController::class, 'me']);

    // 工单
    Route::get('/tickets', [TicketApiController::class, 'index']);
    Route::post('/tickets', [TicketApiController::class, 'store']);
    Route::get('/tickets/{ticket}', [TicketApiController::class, 'show']);
    Route::post('/tickets/{ticket}/replies', [TicketApiController::class, 'reply']);

    // 基础数据
    Route::get('/products', [ReferenceApiController::class, 'products']);
    Route::get('/customers', [ReferenceApiController::class, 'customers']);
    Route::get('/tags', [ReferenceApiController::class, 'tags']);

    // 知识库（App 端浏览已发布文章）
    Route::get('/kb/categories', [KbApiController::class, 'categories']);
    Route::get('/kb/articles', [KbApiController::class, 'index']);
    Route::get('/kb/articles/{article}', [KbApiController::class, 'show']);

    // 通知
    Route::get('/notifications', [NotificationApiController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationApiController::class, 'unreadCount']);
    Route::post('/notifications/{notification}/read', [NotificationApiController::class, 'markRead']);
});
