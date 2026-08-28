<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Ticket;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SupportScanDaily extends Command
{
    protected $signature = 'support:scan-daily';

    protected $description = '每日巡检：SLA 超时工单 + 售后临期/过期客户，自动发送站内通知';

    public function handle(): int
    {
        $this->info('开始每日巡检…');
        $notified = 0;

        // ---- 1. SLA 超时未处理工单 ----
        $overdue = Ticket::whereNotIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])
            ->where('sla_due_at', '<', now())
            ->get();

        foreach ($overdue->groupBy(fn ($t) => $t->assignee_id) as $assigneeId => $tickets) {
            $targetIds = $assigneeId
                ? [(int) $assigneeId]
                : User::whereIn('role', ['agent', 'admin'])->pluck('id')->all();

            $count = $tickets->count();
            $first = $tickets->first();
            NotificationService::notifyUsers($targetIds, "有 {$count} 个工单 SLA 已超时", $first?->no.' '.$first?->subject.' 等工单需尽快处理', route('tickets.index', ['status' => 'open']));
            $notified += count($targetIds);
        }

        // ---- 2. 售后临期（7 天内）与已过期客户 ----
        $expiring = Customer::whereBetween('after_sales_expired_at', [now(), now()->addDays(7)])->get();
        $expired = Customer::where('after_sales_expired_at', '<', now())->get();

        $adminIds = User::where('role', 'admin')->pluck('id')->all();

        if ($expiring->isNotEmpty()) {
            NotificationService::notifyUsers($adminIds, "有 {$expiring->count()} 家客户售后即将到期（7 天内）", $expiring->take(5)->pluck('company')->filter()->implode('、'), route('admin.customers.index', ['warranty' => 'expiring']));
            $notified += count($adminIds);
        }

        if ($expired->isNotEmpty()) {
            NotificationService::notifyUsers($adminIds, "有 {$expired->count()} 家客户售后已过期", $expired->take(5)->pluck('company')->filter()->implode('、'), route('admin.customers.index', ['warranty' => 'expired']));
            $notified += count($adminIds);
        }

        $this->info("巡检完成：SLA 超时 {$overdue->count()} 个，售后临期 {$expiring->count()} 家，已过期 {$expired->count()} 家，共发送通知 {$notified} 条");

        return self::SUCCESS;
    }
}
