<?php

use App\Http\Controllers\AgentRoleController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuickReplyController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketTemplateController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/dashboard/recent', [DashboardController::class, 'recentFragment'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.recent');

// ---- 工单（所有登录用户） ----
// 注意：export/batch/changes 必须声明在 tickets 资源路由之前，避免被 {ticket} 匹配
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('tickets/changes', [TicketController::class, 'changes'])->name('tickets.changes');
});

Route::middleware(['auth', 'verified', 'role:agent'])->group(function () {
    Route::middleware('module:export')->group(function () {
        Route::get('tickets/export', [TicketController::class, 'export'])->name('tickets.export');
    });
    Route::middleware('module:batch')->group(function () {
        Route::post('tickets/batch', [TicketController::class, 'batch'])->name('tickets.batch');
    });
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('tickets', TicketController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('tickets/{ticket}/reply', [TicketController::class, 'reply'])->name('tickets.reply');
    Route::post('tickets/{ticket}/note', [TicketController::class, 'note'])->name('tickets.note');
    Route::patch('tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
    Route::post('tickets/{ticket}/rate', [TicketController::class, 'rate'])->name('tickets.rate');
    Route::post('tickets/{ticket}/claim', [TicketController::class, 'claim'])->name('tickets.claim');
    Route::get('tickets/{ticket}/replies', [TicketController::class, 'pollReplies'])->name('tickets.replies');
    Route::get('attachments/{attachment}/download', [TicketController::class, 'downloadAttachment'])
        ->whereNumber('attachment')
        ->name('attachments.download');
});

// ---- 站内通知（所有登录用户） ----
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::get('notifications/latest', [NotificationController::class, 'latest'])->name('notifications.latest');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
});

// ---- 全局搜索（所有登录用户；客户仅能搜自己的工单）----
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('search', [SearchController::class, 'index'])->name('search');
    Route::get('search/suggest', [SearchController::class, 'suggest'])->name('search.suggest');
});

// ---- 后台业务模块（客服及以上，按模块权限守卫）----
// 前缀可配置（config('app.admin_url')，默认 console），避免使用易被攻击的 /admin
Route::middleware(['auth', 'verified', 'role:agent'])->prefix(config('app.admin_url'))->name('admin.')->group(function () {
    Route::middleware('module:customers')->group(function () {
        // export/import 必须声明在 resource 之前，避免被 {customer} 匹配
        Route::get('customers/export', [CustomerController::class, 'export'])->name('customers.export');
        Route::post('customers/import', [CustomerController::class, 'import'])->name('customers.import');
        Route::resource('customers', CustomerController::class);
    });
    Route::middleware('module:products')->group(function () {
        Route::resource('products', ProductController::class)->only(['index', 'store', 'update', 'destroy']);
    });
    Route::middleware('module:categories')->group(function () {
        Route::resource('categories', CategoryController::class)->except(['show', 'edit']);
    });
    Route::middleware('module:templates')->group(function () {
        Route::resource('ticket-templates', TicketTemplateController::class)->except(['show', 'edit']);
    });
    Route::middleware('module:reports')->group(function () {
        Route::get('reports', ReportController::class)->name('reports');
        Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
    });
    Route::middleware('module:quick-replies')->group(function () {
        Route::resource('quick-replies', QuickReplyController::class)->except(['show', 'edit']);
    });
});

// ---- 用户管理 / 系统设置（仅管理员） ----
Route::middleware(['auth', 'verified', 'role:admin'])->prefix(config('app.admin_url'))->name('admin.')->group(function () {
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::patch('users/{user}/role', [UserController::class, 'updateRole'])->name('users.update-role');
    Route::patch('users/{user}/permissions', [UserController::class, 'updatePermissions'])->name('users.update-permissions');
    Route::patch('users/{user}/agent-role', [UserController::class, 'updateAgentRole'])->name('users.update-agent-role');
    Route::resource('agent-roles', AgentRoleController::class)->except(['show', 'edit']);
    Route::post('users/{user}/offline', [UserController::class, 'toggleOffline'])->name('users.toggle-offline');
    Route::get('settings', [SettingController::class, 'index'])->name('settings');
    Route::post('settings', [SettingController::class, 'save'])->name('settings.save');
    Route::post('settings/sms-test', [SettingController::class, 'testSms'])->name('settings.sms-test');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
