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
// 分层限流（防滥用/刷接口）：
//   throttle:60,1  读操作 —— 每 IP/用户 每分钟 60 次
//   throttle:20,1  写操作 —— 每 IP/用户 每分钟 20 次
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthApiController::class, 'logout'])->middleware('throttle:20,1');
    Route::get('/me', [AuthApiController::class, 'me'])->middleware('throttle:60,1');

    // 工单
    Route::get('/tickets', [TicketApiController::class, 'index'])->middleware('throttle:60,1');
    Route::post('/tickets', [TicketApiController::class, 'store'])->middleware('throttle:20,1');
    Route::get('/tickets/{ticket}', [TicketApiController::class, 'show'])->middleware('throttle:60,1');
    Route::post('/tickets/{ticket}/replies', [TicketApiController::class, 'reply'])->middleware('throttle:20,1');

    // 基础数据
    Route::get('/products', [ReferenceApiController::class, 'products'])->middleware('throttle:60,1');
    Route::get('/customers', [ReferenceApiController::class, 'customers'])->middleware('throttle:60,1');
    Route::get('/tags', [ReferenceApiController::class, 'tags'])->middleware('throttle:60,1');

    // 知识库（App 端浏览已发布文章）
    Route::get('/kb/categories', [KbApiController::class, 'categories'])->middleware('throttle:60,1');
    Route::get('/kb/articles', [KbApiController::class, 'index'])->middleware('throttle:60,1');
    Route::get('/kb/articles/{article}', [KbApiController::class, 'show'])->middleware('throttle:60,1');

    // 通知
    Route::get('/notifications', [NotificationApiController::class, 'index'])->middleware('throttle:60,1');
    Route::get('/notifications/unread-count', [NotificationApiController::class, 'unreadCount'])->middleware('throttle:60,1');
    Route::post('/notifications/{notification}/read', [NotificationApiController::class, 'markRead'])->middleware('throttle:20,1');
});
