<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use App\Services\SettingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * 通知邮件异步发送（P1 优化：邮件从同步改为队列，消除请求阻塞）
 *
 * 触发：NotificationService 检测到 email_notify_enabled=1 且目标用户有邮箱时入队。
 * 由 queue:work（Redis 连接）消费。失败自动重试 3 次（maxTries）。
 */
class SendNotificationEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        public int $userId,
        public string $title,
        public ?string $body = null,
        public ?string $link = null,
    ) {}

    public function handle(): void
    {
        // 重试前再确认开关（避免处理积压任务时误发已关闭的通知邮件）
        if (SettingService::get('email_notify_enabled', '0') !== '1') {
            return;
        }

        $user = User::find($this->userId);
        if (! $user?->email) {
            return;
        }

        try {
            Mail::raw(
                ($this->body ? $this->body."\n\n" : '').($this->link ? '查看详情：'.$this->link : '')."\n\n—— 来自 ".SettingService::siteName().' 自动提醒',
                function ($message) use ($user) {
                    $message->to($user->email)
                        ->subject('【'.SettingService::siteName().'】'.$this->title);
                }
            );
        } catch (\Throwable $e) {
            Log::warning('email notify failed (job): '.$e->getMessage());
            throw $e; // 让队列重试
        }
    }
}
