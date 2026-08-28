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

        // ---- 批量演示工单（分页演示，no: TK-DEMO-1001..1018，与上述 3 条合计 21 条）----
        $bulkUsers = [$customer1->id, $customer2->id, $customer1->id, $customer2->id];
        $bulkCategories = [$catAsk?->id, $catBug?->id, $catSvc?->id, $catBug?->id, $catAsk?->id];
        $bulkProducts = [$prodSass?->id, $prodHw?->id, $prodSass?->id, $prodHw?->id];
        $bulkAssignees = [$agent1->id, $agent2->id, null, $agent1->id, $agent2->id, $admin->id];
        $bulkSubjects = [
            '登录后无法进入工单列表',
            '消息通知不提醒，需要排查',
            '咨询数据导出权限',
            '工单附件上传失败',
            '希望支持批量导入客户',
            '报表统计口径问题',
            '账号绑定微信失败',
            '短信验证码收不到',
            '产品续费价格咨询',
            '页面加载缓慢',
            '历史工单无法检索',
            '希望增加工单模板',
            '接口调用报 500',
            '售后保修期计算疑问',
            '客户资料修改权限',
            '夜间是否有人值班',
            '建议增加满意度回访',
            '关于服务协议的确认',
        ];
        $bulkStatuses = [
            Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_PENDING,
            Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_RESOLVED,
            Ticket::STATUS_OPEN, Ticket::STATUS_PENDING, Ticket::STATUS_IN_PROGRESS,
            Ticket::STATUS_OPEN, Ticket::STATUS_RESOLVED, Ticket::STATUS_OPEN,
            Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_OPEN, Ticket::STATUS_PENDING,
            Ticket::STATUS_OPEN, Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED,
        ];
        $bulkPriorities = [
            Ticket::PRIORITY_NORMAL, Ticket::PRIORITY_HIGH, Ticket::PRIORITY_LOW,
            Ticket::PRIORITY_HIGH, Ticket::PRIORITY_NORMAL, Ticket::PRIORITY_LOW,
            Ticket::PRIORITY_NORMAL, Ticket::PRIORITY_URGENT, Ticket::PRIORITY_NORMAL,
            Ticket::PRIORITY_LOW, Ticket::PRIORITY_NORMAL, Ticket::PRIORITY_NORMAL,
            Ticket::PRIORITY_URGENT, Ticket::PRIORITY_NORMAL, Ticket::PRIORITY_LOW,
            Ticket::PRIORITY_HIGH, Ticket::PRIORITY_LOW, Ticket::PRIORITY_NORMAL,
        ];

        foreach ($bulkSubjects as $i => $subject) {
            $idx = $i + 1;
            $isResolved = in_array($bulkStatuses[$i], [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED]);
            $isOverdue = in_array($i, [1, 12, 15]); // 造 3 条 SLA 超时示例
            $createdAt = now()->subDays(($idx * 3) % 20 + 1)->subHours(($idx * 5) % 24);

            Ticket::updateOrCreate(
                ['no' => sprintf('TK-DEMO-%04d', 1000 + $idx)],
                [
                    'user_id' => $bulkUsers[$i % 4],
                    'category_id' => $bulkCategories[$i % 5],
                    'product_id' => $bulkProducts[$i % 4],
                    'subject' => $subject,
                    'description' => '【演示数据】'.$subject.'，请协助处理，谢谢。',
                    'priority' => $bulkPriorities[$i],
                    'status' => $bulkStatuses[$i],
                    'assignee_id' => $bulkAssignees[$i % 6],
                    'sla_due_at' => $isOverdue ? $createdAt->addHours(2) : $createdAt->addHours(48),
                    'closed_at' => $isResolved ? $createdAt->addDays(2) : null,
                    'last_reply_at' => $isResolved ? $createdAt->addDays(1) : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]
            );

            // 给半数工单补一条客户提问回复
            if ($i % 2 === 0) {
                TicketReply::updateOrCreate(
                    ['ticket_id' => Ticket::where('no', sprintf('TK-DEMO-%04d', 1000 + $idx))->value('id'), 'user_id' => $bulkUsers[$i % 4], 'content' => '请问处理进度如何？'],
                    ['type' => TicketReply::TYPE_REPLY]
                );
            }
        }

        // ---- 补充产品演示数据（分页演示，合计 16 个）----
        $bulkProducts = [
            ['name' => '企业邮箱服务', 'sku' => 'MAIL-001', 'warranty_days' => 365],
            ['name' => '云主机套餐 A', 'sku' => 'CLOUD-A-01', 'warranty_days' => 730],
            ['name' => '云主机套餐 B', 'sku' => 'CLOUD-B-01', 'warranty_days' => 730],
            ['name' => '数据库托管', 'sku' => 'DB-HOST-01', 'warranty_days' => 365],
            ['name' => '文件存储服务', 'sku' => 'OSS-001', 'warranty_days' => 365],
            ['name' => '短信通知服务', 'sku' => 'SMS-SVC-01', 'warranty_days' => 365],
            ['name' => '报表中心专业版', 'sku' => 'BI-PRO-01', 'warranty_days' => 730],
            ['name' => '客服机器人训练包', 'sku' => 'BOT-TRAIN-01', 'warranty_days' => 180],
            ['name' => 'API 网关服务', 'sku' => 'API-GW-01', 'warranty_days' => 365],
            ['name' => '终端运维服务', 'sku' => 'OPS-001', 'warranty_days' => 365],
            ['name' => '备份容灾服务', 'sku' => 'DR-001', 'warranty_days' => 1095],
            ['name' => '专属实施服务', 'sku' => 'IMPL-001', 'warranty_days' => 180],
        ];
        foreach ($bulkProducts as $p) {
            Product::updateOrCreate(['sku' => $p['sku']], $p + ['description' => '【演示数据】'.$p['name'], 'is_active' => true]);
        }

        // ---- 补充客户用户演示数据（分页演示，合计 18 人）----
        $bulkCustomerNames = [
            '张伟', '李娜', '王强', '赵敏', '陈杰', '刘洋', '杨雪', '黄磊', '周芳', '吴刚',
        ];
        $newUsers = [];
        foreach ($bulkCustomerNames as $i => $name) {
            $u = User::updateOrCreate(
                ['email' => 'demo'.$i.'@example.com'],
                ['name' => $name, 'role' => 'customer', 'phone' => '137000000'.str_pad((string) (10 + $i), 2, '0', STR_PAD_LEFT), 'password' => Hash::make('password')]
            );
            $newUsers[] = $u->id;
        }

        // ---- 补充客户档案演示数据（分页演示，合计 16 家）----
        $bulkCompanies = [
            ['中科云创科技有限公司', 0], ['恒远信息技术有限公司', 1], ['蓝海网络科技', 2],
            ['绿洲数据服务', 3], ['极光软件', 4], ['天际电子商务', 5],
            ['万象智能科技', 6], ['飞腾网络工程', 7], ['智汇金融科技', 8],
            ['拓疆物流科技', 9], ['皓月医疗信息', 0], ['金石教育科技', 1],
            ['磐石物联网', 2],
        ];
        $allProducts = Product::orderBy('id')->pluck('id')->all();
        foreach ($bulkCompanies as $i => [$company, $userOffset]) {
            $prodId = $allProducts[$i % count($allProducts)];
            Customer::updateOrCreate(
                ['company' => $company],
                [
                    'contact_name' => $bulkCustomerNames[$i % 10],
                    'phone' => '136000000'.str_pad((string) (20 + $i), 2, '0', STR_PAD_LEFT),
                    'email' => 'c'.$i.'@demo.com',
                    'product_id' => $prodId,
                    'user_id' => $newUsers[$userOffset] ?? null,
                    'registered_at' => now()->subMonths(($i % 12) + 1),
                    'after_sales_expired_at' => now()->subMonths(($i % 12) + 1)->addDays((int) Product::find($prodId)?->warranty_days),
                ]
            );
        }
    }
}
