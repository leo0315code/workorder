<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LoginAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * 登录审计服务：记录每次登录/尝试登录（成功与失败），供管理端审计页查看。
 * 写入失败不影响登录主流程（try/catch 兜底）。
 */
class AuditService
{
    /**
     * 记录一次登录尝试
     *
     * @param  string  $channel  web | admin | phone | wechat | api
     * @param  string  $email  登录账号（邮箱或手机号）
     * @param  bool  $success  是否成功
     * @param  string|null  $reason  失败原因（bad_credentials / not_agent / code_invalid 等）
     */
    public static function logLogin(
        Request $request,
        string $channel,
        string $email,
        bool $success,
        ?string $reason = null,
        ?int $userId = null,
    ): void {
        try {
            LoginAudit::create([
                'user_id' => $userId ?? auth()->id(),
                'email' => Str::limit($email, 191),
                'channel' => $channel,
                'ip' => $request->ip(),
                'user_agent' => Str::limit($request->userAgent() ?? '', 255),
                'success' => $success,
                'reason' => $reason,
                'login_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // 审计失败不影响登录流程
            Log::warning('login audit failed: '.$e->getMessage());
        }
    }
}
