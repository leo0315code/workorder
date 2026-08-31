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
        // ---- 侧边栏菜单（DB 驱动，幂等）----
        $this->call(MenuSeeder::class);

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

        // ---- 标签（多对多，打给示例工单与部分批量工单）----
        $tagDefs = [
            ['name' => '高优先级', 'color' => 'rose'],
            ['name' => '硬件故障', 'color' => 'amber'],
            ['name' => '软件问题', 'color' => 'sky'],
            ['name' => '网络故障', 'color' => 'violet'],
            ['name' => '售后', 'color' => 'emerald'],
            ['name' => '功能需求', 'color' => 'indigo'],
            ['name' => '紧急', 'color' => 'red'],
            ['name' => '待跟进', 'color' => 'cyan'],
        ];
        $tags = collect($tagDefs)->map(fn ($d) => \App\Models\Tag::updateOrCreate(['name' => $d['name']], $d));

        // 示例工单打标签
        $t1?->tags()->syncWithoutDetaching($tags->where('name', '软件问题')->first()?->id);
        $t1?->tags()->syncWithoutDetaching($tags->where('name', '高优先级')->first()?->id);
        $t2?->tags()->syncWithoutDetaching($tags->where('name', '售后')->first()?->id);
        $t3?->tags()->syncWithoutDetaching($tags->where('name', '功能需求')->first()?->id);
        // 批量工单确定性打 1-2 个标签（按索引取固定标签，保证重复 seed 幂等）
        $tagIds = $tags->pluck('id')->all();
        $tagCount = count($tagIds);
        $bulkTicketIds = Ticket::where('no', 'like', 'TK-DEMO-1%')->pluck('id')->all();
        foreach ($bulkTicketIds as $k => $tid) {
            $pickIds = array_slice([$tagIds[$k % $tagCount], $tagIds[($k + 3) % $tagCount]], 0, 1 + ($k % 2));
            Ticket::find($tid)?->tags()->syncWithoutDetaching($pickIds);
        }

        // ---- 工单自定义字段（定义 + 给演示工单填值）----
        $fieldDefs = [
            ['label' => '设备序列号', 'key' => 'serial_no', 'type' => 'text', 'is_required' => false, 'sort' => 10],
            ['label' => '故障类型', 'key' => 'fault_type', 'type' => 'select', 'options' => ['硬件故障', '软件问题', '网络问题'], 'is_required' => false, 'sort' => 20],
            ['label' => '期望解决日期', 'key' => 'expect_date', 'type' => 'date', 'is_required' => false, 'sort' => 30],
        ];
        $fieldDefs = collect($fieldDefs)->map(fn ($d) => \App\Models\TicketFieldDef::updateOrCreate(
            ['key' => $d['key']],
            $d + ['is_active' => true]
        ));

        // 给部分工单填字段值
        $fieldSamples = [
            ['no' => 'TK-DEMO-0001', 'serial_no' => 'X1-2025-0001', 'fault_type' => '软件问题'],
            ['no' => 'TK-DEMO-0002', 'serial_no' => 'HWX1-8842', 'fault_type' => '硬件故障'],
            ['no' => 'TK-DEMO-0003', 'serial_no' => 'X1-2025-0001', 'fault_type' => '功能需求'],
        ];
        foreach ($fieldSamples as $sample) {
            $ft = Ticket::where('no', $sample['no'])->first();
            if (! $ft) {
                continue;
            }
            foreach (['serial_no' => $sample['serial_no'], 'fault_type' => $sample['fault_type']] as $key => $val) {
                $def = $fieldDefs->firstWhere('key', $key);
                if ($def) {
                    \App\Models\TicketFieldValue::updateOrCreate(
                        ['ticket_id' => $ft->id, 'field_def_id' => $def->id],
                        ['value' => $val]
                    );
                }
            }
        }

        // ---- 知识库（2 分类 + 5 篇文章）----
        $kbCatBug = \App\Models\KbCategory::updateOrCreate(['name' => '常见故障'], ['sort' => 10]);
        $kbCatSvc = \App\Models\KbCategory::updateOrCreate(['name' => '售后政策'], ['sort' => 20]);

        $kbArticles = [
            ['kb_category_id' => $kbCatBug->id, 'title' => '登录提示验证码错误怎么办', 'content' => "# 登录提示验证码错误\n\n1. **清理浏览器缓存**并硬刷新（Mac：Cmd+Shift+R / Win：Ctrl+F5）\n2. 确认输入法处于英文状态\n3. 更换浏览器（Chrome / Edge）重试\n4. 仍无法解决：提交工单并附上报错截图"],
            ['kb_category_id' => $kbCatBug->id, 'title' => '系统无法打开/白屏处理步骤', 'content' => "# 系统白屏处理步骤\n\n## 排查\n- 确认网络可达（ping 域名）\n- 清缓存硬刷新\n- 查看浏览器控制台是否有 5xx 报错\n\n## 升级\n- 携带控制台截图提交故障工单，标注发生时间"],
            ['kb_category_id' => $kbCatBug->id, 'title' => '数据报表导出格式说明', 'content' => "# 报表导出说明\n\n| 导出格式 | 用途 |\n|---|---|\n| CSV | 通用，Excel/WPS 直开 |\n| Excel（开发中） | 财务对账 |\n\n> 导出文件为 UTF-8 编码，Excel 打开无需额外设置"],
            ['kb_category_id' => $kbCatSvc->id, 'title' => '硬件终端续保政策', 'content' => "# 硬件终端续保政策\n\n- 标准质保期：**3 年**（自注册日起）\n- 续保方案：1 年 / 2 年 / 3 年三档\n- 续保价格随终端型号与采购量浮动\n\n## 办理流程\n1. 提交售后工单，备注「申请续保」\n2. 客服 1 个工作日内对接报价"],
            ['kb_category_id' => $kbCatSvc->id, 'title' => 'SaaS 账号权限管理说明', 'content' => "# 账号权限管理\n\n- 管理员可在「用户管理」维护客服角色与模块权限\n- 客服角色模板在「角色管理」中配置\n\n```\n角色 = 系统角色(customer/agent/admin)\n     + 客服角色模板(模块权限)\n```"],
        ];
        foreach ($kbArticles as $i => $a) {
            \App\Models\KbArticle::updateOrCreate(
                ['title' => $a['title']],
                $a + ['created_by' => $agent1->id, 'views' => 3 + ($i * 7), 'is_published' => true]
            );
        }
    }
}
