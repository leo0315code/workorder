<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---- 用户 ----
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => '系统管理员', 'role' => 'admin', 'phone' => '13800000001', 'password' => Hash::make('password')]
        );
        $agent1 = User::updateOrCreate(
            ['email' => 'agent@example.com'],
            ['name' => '客服小张', 'role' => 'agent', 'phone' => '13800000002', 'password' => Hash::make('password')]
        );
        $agent2 = User::updateOrCreate(
            ['email' => 'agent2@example.com'],
            ['name' => '客服小李', 'role' => 'agent', 'phone' => '13800000003', 'password' => Hash::make('password')]
        );
        $customer1 = User::updateOrCreate(
            ['email' => 'customer@example.com'],
            ['name' => '客户小王', 'role' => 'customer', 'phone' => '13800000004', 'password' => Hash::make('password')]
        );
        $customer2 = User::updateOrCreate(
            ['email' => 'customer2@example.com'],
            ['name' => '客户老赵', 'role' => 'customer', 'phone' => '13800000005', 'password' => Hash::make('password')]
        );

        // 种子用户直接标记为已验证（演示环境）
        User::query()->whereNull('email_verified_at')->update(['email_verified_at' => now()]);

        // ---- 系统配置 ----
        $settings = [
            'site_name' => '工单系统',
            'auto_assign' => '1',
            'sla_low' => '72',
            'sla_normal' => '48',
            'sla_high' => '24',
            'sla_urgent' => '8',
            'work_hours_enabled' => '1',
            'work_start' => '09:00',
            'work_end' => '18:00',
            'work_days' => '1,2,3,4,5',
        ];
        foreach ($settings as $key => $value) {
            \App\Models\Setting::updateOrCreate(['setting_key' => $key], ['value' => $value]);
        }

        // ---- 分类 ----
        $categories = [
            ['name' => '产品咨询', 'description' => '产品功能、使用、报价咨询'],
            ['name' => '故障报修', 'description' => '系统报错、无法使用等故障'],
            ['name' => '功能建议', 'description' => '功能改进与需求建议'],
            ['name' => '售后服务', 'description' => '安装、维护、质保相关'],
        ];
        foreach ($categories as $c) {
            Category::updateOrCreate(
                ['name' => $c['name']],
                $c + ['slug' => \Illuminate\Support\Str::slug($c['name']).'-'.substr(md5($c['name']), 0, 4), 'is_active' => true]
            );
        }

        // ---- 快捷回复模板 ----
        $quickReplies = [
            ['title' => '已收到，正在排查', 'content' => "您好，已收到您的问题，我们正在排查，稍后给您答复。\n如有更多信息（截图、报错文本）可一并补充，有助于尽快定位。"],
            ['title' => '请清理缓存重试', 'content' => "您好，建议先清理浏览器缓存并硬刷新（Mac: Cmd+Shift+R）后重试；若仍复现，请提供具体报错截图，我们继续跟进。"],
            ['title' => '已解决并关闭', 'content' => "您好，经确认问题已解决。若后续还有其他问题，欢迎随时提交新工单，感谢您的反馈与支持！"],
            ['title' => '抱歉让您久等', 'content' => "您好，非常抱歉让您久等，该问题我们正在加急处理中，预计今天内给您进展反馈。"],
        ];
        foreach ($quickReplies as $qr) {
            \App\Models\QuickReply::updateOrCreate(['title' => $qr['title']], $qr + ['is_active' => true]);
        }

        // ---- 产品 ----
        $products = [
            ['name' => '智能客服平台（SaaS）', 'sku' => 'SAAS-CS-01', 'description' => '云端智能客服系统，含机器人+人工坐席', 'warranty_days' => 365],
            ['name' => '数据分析系统', 'sku' => 'BI-ANA-01', 'description' => '经营数据分析与报表系统', 'warranty_days' => 730],
            ['name' => '硬件终端 X1', 'sku' => 'HW-X1-01', 'description' => '门店智能终端一体机', 'warranty_days' => 1095],
            ['name' => '定制开发服务', 'sku' => 'DEV-CUS-01', 'description' => '按需定制开发，交付后质保', 'warranty_days' => 180],
        ];
        foreach ($products as $p) {
            Product::updateOrCreate(['sku' => $p['sku']], $p + ['is_active' => true]);
        }

        // ---- 客户档案 ----
        $prodSass = Product::where('sku', 'SAAS-CS-01')->first();
        $prodHw = Product::where('sku', 'HW-X1-01')->first();

        Customer::updateOrCreate(
            ['user_id' => $customer1->id],
            [
                'company' => '星辰科技有限公司',
                'contact_name' => '小王',
                'phone' => '13800000004',
                'email' => 'customer@example.com',
                'product_id' => $prodSass?->id,
                'registered_at' => now()->subMonths(8),
                'after_sales_expired_at' => now()->subMonths(8)->addDays($prodSass->warranty_days),
            ]
        );
        Customer::updateOrCreate(
            ['user_id' => $customer2->id],
            [
                'company' => '恒信贸易有限公司',
                'contact_name' => '老赵',
                'phone' => '13800000005',
                'email' => 'customer2@example.com',
                'product_id' => $prodHw?->id,
                'registered_at' => now()->subMonths(1),
                'after_sales_expired_at' => now()->addMonths(35), // 接近临期示例
            ]
        );

        // ---- 示例工单 ----
        $catBug = Category::where('name', '故障报修')->first();
        $catAsk = Category::where('name', '产品咨询')->first();
        $catSvc = Category::where('name', '售后服务')->first();

        $t1 = Ticket::updateOrCreate(
            ['no' => 'TK-DEMO-0001'],
            [
                'user_id' => $customer1->id,
                'customer_id' => Customer::where('user_id', $customer1->id)->first()?->id,
                'category_id' => $catBug?->id,
                'product_id' => $prodSass?->id,
                'subject' => '登录后台一直提示验证码错误',
                'description' => "登录智能客服平台管理后台时，输入验证码后一直提示「验证码错误」，刷新多次仍然复现。\n浏览器：Chrome 最新版\n账号：customer@example.com",
                'priority' => Ticket::PRIORITY_HIGH,
                'status' => Ticket::STATUS_IN_PROGRESS,
                'assignee_id' => $agent1->id,
                'sla_due_at' => now()->addHours(20),
                'last_reply_at' => now()->subMinutes(30),
            ]
        );
        TicketReply::updateOrCreate(
            ['ticket_id' => $t1->id, 'user_id' => $customer1->id, 'content' => '麻烦尽快帮忙看一下，今天要提交月度数据。'],
            ['type' => TicketReply::TYPE_REPLY]
        );
        TicketReply::updateOrCreate(
            ['ticket_id' => $t1->id, 'user_id' => $agent1->id, 'content' => '已收到，正在排查验证码服务。请先尝试清理浏览器缓存并硬刷新（Cmd+Shift+R）。'],
            ['type' => TicketReply::TYPE_REPLY]
        );

        $t2 = Ticket::updateOrCreate(
            ['no' => 'TK-DEMO-0002'],
            [
                'user_id' => $customer2->id,
                'customer_id' => Customer::where('user_id', $customer2->id)->first()?->id,
                'category_id' => $catAsk?->id,
                'product_id' => $prodHw?->id,
                'subject' => '询问硬件终端 X1 的续保政策',
                'description' => '我们采购的 X1 终端即将过保，想了解续保费用和条款。',
                'priority' => Ticket::PRIORITY_NORMAL,
                'status' => Ticket::STATUS_OPEN,
                'assignee_id' => null,
                'sla_due_at' => now()->addHours(48),
            ]
        );

        $t3 = Ticket::updateOrCreate(
            ['no' => 'TK-DEMO-0003'],
            [
                'user_id' => $customer1->id,
                'customer_id' => Customer::where('user_id', $customer1->id)->first()?->id,
                'category_id' => $catSvc?->id,
                'product_id' => $prodSass?->id,
                'subject' => '希望增加报表导出 Excel 功能',
                'description' => '月度报表目前只能在线查看，希望支持导出 Excel，方便财务对账。',
                'priority' => Ticket::PRIORITY_LOW,
                'status' => Ticket::STATUS_RESOLVED,
                'assignee_id' => $agent2->id,
                'sla_due_at' => now()->subDays(2),
                'closed_at' => now()->subDays(1),
                'last_reply_at' => now()->subDay(),
            ]
        );
        TicketReply::updateOrCreate(
            ['ticket_id' => $t3->id, 'user_id' => $agent2->id, 'content' => '该需求已排入 9 月版本计划，上线后第一时间同步您。'],
            ['type' => TicketReply::TYPE_REPLY]
        );
        TicketReply::updateOrCreate(
            ['ticket_id' => $t3->id, 'user_id' => $agent2->id, 'content' => '客户对排期满意，先按建议单处理，内部跟踪排期即可。'],
            ['type' => TicketReply::TYPE_NOTE]
        );
    }
}
