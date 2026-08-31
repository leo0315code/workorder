<?php

use App\Http\Controllers\AgentRoleController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KbController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\TicketFieldDefController;
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

/*
|--------------------------------------------------------------------------
| 路由结构总览（按访问角色分层）
|--------------------------------------------------------------------------
|
| 1. 公开        ：首页重定向 + 认证路由（auth.php）
| 2. 登录用户    ：仪表盘 / 工单 / 通知 / 搜索 / 知识库阅读 / 个人资料
| 3. 客服后台    ：业务模块（按模块权限守卫），前缀 config('app.admin_url')
| 4. 管理员后台  ：用户 / 设置 / 菜单 / 工单字段
|
| 路由顺序注意：tickets 的静态子路由（export/changes/batch）必须声明在
| tickets/{ticket} 之前，否则会被 {ticket} 参数匹配。
*/  

// ---------------------------------------------------------------------------
// 1. 公开
// ---------------------------------------------------------------------------
Route::get('/', fn () => redirect()->route('dashboard'));

require __DIR__.'/auth.php';

// ---------------------------------------------------------------------------
// 2. 登录用户（auth + verified）
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'verified'])->group(function () {

    // ---- 仪表盘 ----
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('dashboard/recent', [DashboardController::class, 'recentFragment'])->name('dashboard.recent');

    // ---- 工单 ----
    // 静态子路由在前（export/changes/batch），资源路由在后，避免被 {ticket} 匹配
    Route::middleware('module:export')->group(function () {
        Route::get('tickets/export', [TicketController::class, 'export'])->name('tickets.export');
    });
    Route::get('tickets/changes', [TicketController::class, 'changes'])->name('tickets.changes');
    Route::middleware('module:batch')->group(function () {
        Route::post('tickets/batch', [TicketController::class, 'batch'])->name('tickets.batch');
    });
    Route::resource('tickets', TicketController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('tickets/{ticket}/reply', [TicketController::class, 'reply'])->name('tickets.reply');
    Route::post('tickets/{ticket}/note', [TicketController::class, 'note'])->name('tickets.note');
    Route::patch('tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
    Route::post('tickets/{ticket}/rate', [TicketController::class, 'rate'])->name('tickets.rate');
    Route::post('tickets/{ticket}/claim', [TicketController::class, 'claim'])->name('tickets.claim');
    Route::post('tickets/{ticket}/tags', [TicketController::class, 'syncTags'])->name('tickets.tags');
    Route::get('tickets/{ticket}/replies', [TicketController::class, 'pollReplies'])->name('tickets.replies');
    Route::get('attachments/{attachment}/download', [TicketController::class, 'downloadAttachment'])
        ->whereNumber('attachment')
        ->name('attachments.download');

    // ---- 站内通知 ----
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::get('notifications/latest', [NotificationController::class, 'latest'])->name('notifications.latest');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    // ---- 全局搜索（客户仅能搜自己的工单）----
    Route::get('search', [SearchController::class, 'index'])->name('search');
    Route::get('search/suggest', [SearchController::class, 'suggest'])->name('search.suggest');

    // ---- 知识库阅读页（仅已发布文章）----
    Route::get('kb/{article}', [KbController::class, 'show'])->whereNumber('article')->name('kb.show');
});

// 个人资料（auth 即可，未验证邮箱也可进入）
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ---------------------------------------------------------------------------
// 3. 客服后台（role:agent，按模块权限守卫；前缀可配置避免 /admin 被攻击）
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'verified', 'role:agent'])->prefix(config('app.admin_url'))->name('admin.')->group(function () {

    Route::middleware('module:customers')->group(function () {
        // export/import 必须在 resource 之前，避免被 {customer} 匹配
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

    // ---- 知识库（基础能力，所有客服可用）----
    Route::get('kb', [KbController::class, 'index'])->name('kb.index');
    Route::post('kb', [KbController::class, 'store'])->name('kb.store');
    Route::patch('kb/{article}', [KbController::class, 'update'])->name('kb.update');
    Route::delete('kb/{article}', [KbController::class, 'destroy'])->name('kb.destroy');
    Route::post('kb/categories', [KbController::class, 'storeCategory'])->name('kb.categories.store');
    Route::patch('kb/categories/{category}', [KbController::class, 'updateCategory'])->name('kb.categories.update');
    Route::delete('kb/categories/{category}', [KbController::class, 'destroyCategory'])->name('kb.categories.destroy');
});

// ---------------------------------------------------------------------------
// 4. 管理员后台（role:admin）
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'verified', 'role:admin'])->prefix(config('app.admin_url'))->name('admin.')->group(function () {

    // ---- 用户管理（含客服角色与在线状态）----
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::patch('users/{user}/role', [UserController::class, 'updateRole'])->name('users.update-role');
    Route::patch('users/{user}/permissions', [UserController::class, 'updatePermissions'])->name('users.update-permissions');
    Route::patch('users/{user}/agent-role', [UserController::class, 'updateAgentRole'])->name('users.update-agent-role');
    Route::post('users/{user}/offline', [UserController::class, 'toggleOffline'])->name('users.toggle-offline');
    Route::resource('agent-roles', AgentRoleController::class)->except(['show', 'edit']);

    // ---- 系统设置 ----
    Route::get('settings', [SettingController::class, 'index'])->name('settings');
    Route::post('settings', [SettingController::class, 'save'])->name('settings.save');
    Route::post('settings/sms-test', [SettingController::class, 'testSms'])->name('settings.sms-test');

    // ---- 菜单管理（DB 驱动侧边栏）----
    Route::get('menus', [MenuController::class, 'index'])->name('menus.index');
    Route::post('menus', [MenuController::class, 'store'])->name('menus.store');
    Route::patch('menus/{menu}', [MenuController::class, 'update'])->name('menus.update');
    Route::post('menus/{menu}/field', [MenuController::class, 'updateField'])->name('menus.update-field');
    Route::delete('menus/{menu}', [MenuController::class, 'destroy'])->name('menus.destroy');

    // ---- 工单自定义字段 ----
    Route::get('field-defs', [TicketFieldDefController::class, 'index'])->name('field-defs.index');
    Route::post('field-defs', [TicketFieldDefController::class, 'store'])->name('field-defs.store');
    Route::patch('field-defs/{def}', [TicketFieldDefController::class, 'update'])->name('field-defs.update');
    Route::delete('field-defs/{def}', [TicketFieldDefController::class, 'destroy'])->name('field-defs.destroy');
});
