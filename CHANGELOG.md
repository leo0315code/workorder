# 变更记录（CHANGELOG）

本文件记录工单系统的重要功能变更与部署注意事项。

---

## 2026-09-12 — v2.3.4：剩余服务补测（WebSocket/Ticket/Search/Setting/Menu）

### 优化

按风险面优先级补齐零覆盖服务的测试：

- **WebSocketServiceTest（8 例）**：HMAC 签名确定性/排序无关/非法 rooms 过滤/与服务端 Events 一致；frontendWsUrl 三分支（显式 VITE_WS_URL / 代理路径 / 端口直连 + wss 适配）
- **TicketServiceTest（12 例）**：filterQuery 角色隔离与全维度筛选（mine/unassigned/overdue/warning/status/priority/category/product/q/tag）、duplicateOf、nextNo、logAction、authorizeView/Staff 越权 403、自定义字段必填校验与 upsert
- **SearchServiceTest（8 例）**：客户数据隔离（仅搜自己工单，客户/产品返回空）、按 no/subject/description/company/contact/phone/sku 检索、search 三段结构、suggest 权限差异
- **SettingServiceTest + MenuServiceTest（11 例）**：配置读写/默认值/批量跳过 null、菜单受众过滤/admin_only/死链跳过/高亮
- `AuditService` 已被既有测试间接覆盖（单行 create），不重复补

### 验证

- 新增 39 用例全通过 ✅
- 全量 **297 用例 / 815 断言**（258+39）全通过 ✅

---

## 2026-09-12 — v2.3.3：微信扫码登录链路补测试（WechatLoginTest）

### 优化

- 补齐微信登录链路测试（此前 5 条路由 + WechatService **完全无测试**，属认证安全面）：
  - QR 会话创建（演示模式 qr_url=null、缓存写入）
  - 状态轮询：未知会话 expired / 未扫码 pending / 已绑定用户自动登录 / 未绑定 need_bind
  - 模拟扫码：过期会话 404
  - 绑定已有账号（含密码错误记 bad_credentials 审计 + 拒绝登录）
  - 注册新账号 / 手机号已注册复用不新建
  - 真实回调：缺参拒绝、code 换 openid（Http fake）、微信错误透传
  - 绑定页无 ready 状态返回 419

### 验证

- WechatLoginTest 14 用例（51 断言）全通过 ✅
- 全量 **258 用例 / 737 断言**（244+14）全通过 ✅

---

## 2026-09-12 — v2.3.2：报表服务补测试（ReportServiceTest）

### 优化

- 补齐报表统计服务测试（此前无任何测试，属 P1 风险面）：
  - `summary` / `byStatus` / `byPriority` / `byCategory`：计数与分组（含未分类、窗口外排除）
  - `dailySeries` / `ratingDailySeries`：趋势序列（无数据当天补 0 / null）
  - `agents`：客服排行（handled/replies/avg_first_response_hours/avg_resolve_hours/overdue，SQLite julianday 分支）
  - `ratingStats`：满意度（count/avg/positive/solved 及比率、无数据归零）
  - `normalizeDays` / `startOf`：参数归一化
- 测试基建要点：`created_at` 不在 `fillable`，用 `forceFill()->save()` 回填时间；`Category` 的 slug 由控制器生成，测试需手动补

### 验证

- ReportServiceTest 12 用例（45 断言）全通过 ✅
- 全量 **244 用例 / 686 断言**（232+12）全通过 ✅

---

## 2026-09-12 — v2.3.1：自动分配服务补测试（AutoAssignServiceTest）

### 优化

- 补齐自动分配核心逻辑测试（此前无任何测试，属 P1 风险面）：
  - `pickFromCandidates` 纯函数：实时不可用回退全量 / 在线优先 / 无人在线 / 空候选
  - `pick()` 集成：开关关闭、无候选、manual_offline 排除、agent 优先 admin、活跃工单数最少优先、已解决/关闭不计负载、无人在线、网关不可用回退
- 可测试性改造：`AutoAssignService` 增加 `$onlineUidsProvider` 静态注入点（默认 null 走真实 Gateway，测试用闭包替换），生产行为不变
- 测试中未使用 Mockery overload（`GatewayClient\Gateway` 在应用 bootstrap 时已被真实加载，overload 静默失效），改用注入方案

### 验证

- AutoAssignServiceTest 12 用例全通过 ✅
- 全量 **232 用例 / 641 断言**（220+12）全通过 ✅

---

## 2026-09-09 — v2.3.0：消息内容 URL 自动识别为可点击链接

### 新增

- 对话消息/内部备注中的 `http(s)://` 链接自动变为可点击（新窗口打开，`rel="noopener"` 防钓鱼），纯文本/换行原样保留
- 实现：
  - 模型 `TicketReply::renderedContent()`：先 HTML 转义（防 XSS）→ URL 链接化 → 换行转 `<br>`
  - 静态气泡 `reply.blade.php` 改用 `{!! renderedContent() !!}`（方法内已自行转义）
  - 实时渲染 `renderReply` 增加 `linkify()` 与后端逻辑一致（转义 + URL 正则）
- 只改显示层，存储内容不变

### 验证

- 普通文本、URL 变链接、`<script>` 仍被转义、换行保留 ✅
- 220 用例（219+1）全通过 ✅

---

## 2026-09-09 — v2.2.9：实时回复对齐图片缩略图 + 轮询接口携带附件

### 优化

- **WS/轮询实时新增的回复此前只有纯文本**（附件不显示），与静态渲染不一致；现 `replyPayload` 携带附件载荷（id/文件名/大小/mime/下载 URL），轮询接口 `replies` 同样加载并返回附件
- 前端 `renderReply` 动态渲染与静态 partial 对齐：图片类附件显示缩略图（点击放大灯箱）、非图片显示下载链接

### 验证

- reply#20 payload 正确返回 PNG 附件与下载 URL ✅
- 219 用例（218+1：轮询接口含附件结构与 mime/download_url）全通过 ✅

---

## 2026-09-09 — v2.2.8：灯箱点击大图无法关闭

### 修复

- 灯箱大图铺满屏幕时点不到"空白"关不掉：图片原来挂 `@click.stop` 阻止冒泡，改为 `pointer-events-none` 点击穿透到遮罩，**点图片任意处或 Esc 均可关闭**

---

## 2026-09-09 — v2.2.7：图片附件缩略图 + 点击放大灯箱预览

### 新增

- 气泡内图片类附件（`mime_type` 为 `image/*`）渲染为缩略图（`max-h-48` 圆角带描边），下方保留原文件名/大小下载链接
- 点击缩略图打开全屏灯箱（黑色 80% 遮罩 + 居中大图，Esc 或点击空白关闭）；通过 Alpine `$dispatch('open-image', url)` + show 页全局监听实现
- 非图片附件维持原下载链接样式；缩略图直接请求鉴权下载路由（同源 session 生效，`Content-Disposition: attachment` 不影响 `<img>` 渲染）

### 验证

- reply#20（PNG 附件）渲染出 `<img>` 缩略图与 open-image 事件 ✅
- 218 用例全通过 ✅

---

## 2026-09-09 — v2.2.6：修复聊天附件下载 404 + 根除测试套件"下班后挂"定时炸弹

### 修复一：回复附件下载 404

- **根因**：`TicketService::download` 的工单归属解析只认 `attachable_type === Ticket`；v2.2.3 起回复附件挂在 `TicketReply` 上，解析为 null → `abort_unless($ticket, 404)`
- **修复**：归属解析改为 match——`Ticket` 直取；`TicketReply` 经 `$reply->ticket` 回溯；其他类型 404
- **测试**：+1 回归用例（回复附件：归属者 200、无关客户 403）

### 修复二：测试套件时间炸弹

- **现象**：18:00 后跑测试，`AttachmentSecurityTest`/`TicketFieldTest` 各挂 2 例（工单/附件未落库）；18:00 前跑全过
- **根因**：`work_hours_enabled` 默认开启（09:00–18:00），这 4 个用例 POST `tickets.store` 但没有 seeding 禁用工作时间，下班后客户提交被工作时段校验拦截
- **修复**：基类 `tests/TestCase.php` 统一 `firstOrCreate work_hours_enabled=0`（带 settings 表存在性保护）；移除各子类重复 seeding；需要验证工作时间逻辑的用例仍可显式 `updateOrCreate` 覆盖（如 ApiTest 23:59-23:58、TicketFlowTest 00:00-00:01，均与当前时刻无关）

### 验证

- 218 用例（217+1）全通过 ✅

---

## 2026-09-09 — v2.2.5：修复附件气泡仍被撑高（pre-wrap 继承问题）

### 修复

v2.2.4 后带附件的消息气泡依然超高（正文一行 + 附件一行，气泡却 380px）：

- **根因**：气泡容器上的 `whitespace-pre-wrap` 会**继承给所有子元素**——附件区块、`<a>`、`<svg>` 全部变成 pre-wrap，Blade 模板源码里的换行和深层缩进被当成真实空白原样渲染，每个源码换行变成一行、28 格缩进渲染成大段空白
- **修复**：`reply.blade.php` 中把 `whitespace-pre-wrap` 从气泡容器收窄到只包正文的内层 `<div>`，附件区块回到正常流，模板缩进/换行正常折叠

### 验证

- 重新渲染 reply#20（正文+PNG 附件）：正文与附件紧凑排列，无多余空白行 ✅
- 217 用例全通过 ✅

---

## 2026-09-09 — v2.2.4：修复消息气泡被尾部空行撑高

### 修复

单行文字的消息气泡显示得非常高（文字挤在顶部、下方大片空白）：

- **根因**：textarea 提交的内容带末尾换行/空行，入库未归一化；气泡用 `whitespace-pre-wrap` 渲染，尾部空行原样撑高气泡（WS 实时渲染同样受影响）
- **修复**：`TicketReply` 增加 `setContentAttribute` 归一化 mutator——统一 `\r\n`/`\r` → `\n` 并 trim 首尾空白；对 Web 回复、内部备注、API 三个入库口统一生效
- **存量数据**：清理历史回复中带首尾空白的 2 条（预览确认后 UPDATE）

### 验证

- 217 用例全通过 ✅
- 截图对应消息（16:09 / 16:10）内容已干净，剩余异常 0 条 ✅

---

## 2026-09-09 — v2.2.3：回复附件在对话气泡内展示

> 测试：216 → **217** 用例全通过（+1）

### 修复

用户端沟通时提交附件"只显示内容看不到附件"：

- **根因**：回复附件此前挂载到**工单级**（attachable=Ticket），只在工单顶部的独立"附件"汇总区显示，对话气泡里看不到
- **修复**：回复附件改为挂到**该回复**（多态 attachable=TicketReply），在**对话气泡内**展示附件下载链接（文件名 + 大小）
- `storeAttachments` 支持指定挂载目标（默认工单，reply 传入回复）
- `TicketReply` 增加 `attachments()` 多态关联；show 加载 replies 时 eager load attachments
- 工单创建时的附件仍在顶部"工单附件"区展示

### 验证

- 回复上传附件 → 附件挂到 reply（`attachable_type=TicketReply`），气泡内显示 ✅
- 工单级附件不重复挂载 ✅
- 新增 Feature 测试（reply attachment attached to reply）✅

---

## 2026-09-09 — v2.2.2：修复登录页 Alpine 报错 old is not defined

> 测试：215 → **216** 用例全通过（+1）

### 修复

登录/资料页报 `Uncaught ReferenceError: old is not defined`（Alpine 运行时）：

- **根因**：Breeze 脚手架遗留的 `:value="old('xxx')"` 写法——Alpine 动态绑定属性（冒号前缀）**不经 Blade 处理**，`old()` 被原样输出为 JS 表达式 → Alpine 执行时报错
- **修复**：4 处 `:value="old(...)"` → `value="{{ old(...) }}"`（服务端渲染）：
  - `auth/login.blade.php`（email）
  - `auth/admin-login.blade.php`（account）
  - `profile/partials/update-profile-information-form.blade.php`（name + email）
- **回归测试**：新增用例断言登录页 email 为服务端 `value` 且产物无 `old(` 表达式

### 验证

- 渲染后登录页：`name="email" value=""`（无 Alpine 表达式）✅
- 两个登录页产物 `old('` 出现 0 次 ✅
- 216 测试全过 ✅

---

## 2026-09-09 — v2.2.1：客服可编辑工单自定义字段

> 测试：213 → **215** 用例全通过（+2）

### 新增

工单详情页「补充信息」（自定义字段）**支持客服编辑**：

- 此前字段只在**创建工单时**可填，后续无法修改
- 现在客服在详情页点「编辑」→ 内联表单修改 → 保存（字段值先删后建，**空值=清除该字段**）
- 操作记录追加"补充信息已更新"
- 详情页展示优化：空值显示「未填写」灰字，下拉类型值用徽标展示

### 验证

- 客服编辑字段 → 落库成功 ✅
- 提交空值 → 字段值被删除（边界）✅
- 详情页编辑表单渲染（3 字段 + 保存按钮）✅
- 新增 2 个 Feature 测试 ✅

---

## 2026-09-09 — v2.2.0：用户级通知偏好

> 测试：210 → **213** 用例全通过（+3）

### 新增

每个用户可独立控制接收哪些通知（此前只有全局开关）：

| 偏好 | 说明 |
|---|---|
| **邮件提醒** | 工单动态是否发邮件（需系统邮件通道开启，且个人偏好开启才发） |
| **SLA 预警**（客服/管理员可见） | 超时 / 临期 / 待认领升级提醒 |
| **工单通知** | 新工单 / 回复 / 状态变更 / 指派提醒 |

- **users 表新增 `notification_prefs` JSON 字段**（null = 全部默认开启）
- **NotificationService** 支持 `channel` 参数（sla/ticket），按用户偏好过滤：偏好关闭则不入库、不推送、不发邮件
- **全部通知调用点**已标注 channel：SLA 巡检 3 处（sla）、工单指派/回复/状态/@提及 15 处（ticket）
- **个人资料页**新增「通知偏好」面板（所有角色可用，SLA 选项仅客服/管理员显示）

### 验证

- 关闭 SLA 偏好 → `notifyEnabled('sla')=false`，SLA 类通知不入库 ✅
- 未设置偏好（null）→ 全部默认开启 ✅
- 保存偏好 → 落库 `{"sla":false,"email":true,"ticket":true}` ✅
- 新增 3 个 Feature 测试 ✅

### 部署注意

```bash
php artisan migrate   # 新增 notification_prefs 列
```

---

## 2026-09-08 — v2.1.9：后台管理登录支持用户名

> 测试：209 → **210** 用例全通过（+1）

### 新增

- **users 表新增 `username` 字段**（唯一、可空，兼容已有账号仅邮箱/手机号）
- **管理后台登录支持「用户名 或 邮箱」**：登录页字段改为「用户名 / 邮箱」，后端按 `email = ? OR username = ?` 查找
- **用户管理**：新增用户时必填用户名（字母/数字/下划线/横线），列表展示用户名，搜索支持用户名
- 演示账号已补用户名：`admin` / `agent` / `agent2` / `customer` / `customer2`
- UserFactory 默认生成 username

### 验证

- 用用户名 `admin` + 密码登录 → 302 进入后台 ✅
- 邮箱登录兼容（CLI 双通道查找均匹配 id=1）✅
- 新增 Feature 测试 `test_admin_login_accepts_username` ✅

### 部署注意

```bash
php artisan migrate   # 新增 username 列
php artisan db:seed   # 演示账号补 username（可重复执行）
```

---

## 2026-09-08 — v2.1.8：对外 API 全量限流

> 测试：208 → **209** 用例全通过（+1）

### 新增

对外 API（`/api/*`）全量接入分层限流（此前仅登录接口有限流）：

| 类型 | 限流 | 覆盖 |
|---|---|---|
| 读操作 | 每 IP 每分钟 60 次 | 工单列表/详情、基础数据（产品/客户/标签）、知识库、通知查询、/me |
| 写操作 | 每 IP 每分钟 20 次 | 建单、回复、通知已读、登出 |
| 登录 | 每 IP 每分钟 5 次（原有） | /auth/login |

超限返回 **429 Too Many Requests**。

### 验证

- 新增 Feature 测试：连发 21 次建单 → 第 21 次 429 ✅
- 真实 token 请求读接口 5 次全 200（60/min 内正常）✅

---

## 2026-09-08 — v2.1.7：仪表盘新增「我的待处理 / 待认领」工单列表

> 测试：207 → **208** 用例全通过（+1）

### 新增

客服/管理员仪表盘新增两个面板（最近工单之前）：

| 面板 | 内容 |
|---|---|
| **我的待处理** | 指派给我的未解决工单 Top 5，按 SLA 排序（超时 → 临期 → 正常），带超时/临期徽标 + 优先级；「查看全部」跳我的工单 |
| **待认领** | 无负责人工单 Top 5（最新在前），带相对时间与「认领」入口；「查看全部」跳未指派筛选 |

### 验证

- 管理员登录 → 面板渲染，「我的待处理」显示 5 条超时工单（带红色"超时"徽标）✅
- 工单链接全部带 `/console` 前缀 ✅
- 新增 1 个 Feature 测试（dashboard my open / unassigned）✅

---

## 2026-09-08 — v2.1.6：批量操作扩展（改优先级 + 指派通知）

> 测试：205 → **207** 用例全通过（+2）

### 新增

工单列表批量操作新增能力：

| 操作 | 说明 |
|---|---|
| **批量改优先级** | 勾选多个工单 → 批量设为 低/普通/高/紧急；只改实际变化项并记录操作日志 |
| **批量指派通知** | 批量指派时若负责人发生变化，**自动站内通知新负责人**（此前为静默指派） |

前端批量操作栏新增「批量改优先级」选项 + 优先级下拉（按所选操作联动显示）。

### 验证

- 批量改优先级：2 个工单 normal/low → urgent 成功，操作日志留痕 ✅
- 批量指派：新负责人收到「工单已指派给你」站内通知 ✅
- 新增 2 个 Feature 测试（batch priority / batch assign notify）✅

---

## 2026-09-08 — v2.1.5：报表新增每日满意度趋势

> 测试：204 → **205** 用例全通过（+1）

### 新增

报表页新增**「每日满意度趋势」**面板（近 7/30/90 天随切换联动）：

- 柱状图展示每日平均评分（满分 5），按分数分色：≥4 绿 / ≥3 黄 / <3 红
- 无评价当天显示为空（避免误导为零分）
- CSV 导出同步增加「日期 / 平均满意度 / 评价数」段（与页面同口径）
- panel 组件补 `star` 图标

### 验证

- 造 3 条不同日期评价（5/3/4 分）→ 服务层趋势正确（今日 avg 4.0, count 3），页面非空渲染 ✅
- 新增 1 个 Feature 测试（ratingDailySeries 聚合）✅

---

## 2026-09-08 — v2.1.4：SLA 临期筛选 + 知识库全文检索

> 测试：202 → **204** 用例全通过（+2）

### 新增

| 功能 | 说明 |
|---|---|
| **工单列表 SLA 临期筛选** | 新增橙色「SLA 临期」筛选按钮（剩余 6 小时内到期），与「SLA 超时」并列；后端 `filterQuery` 支持 `warning=1` |
| **知识库全文检索** | 搜索从仅标题升级为**标题 + 内容**（LIKE 匹配，适合中小知识库），搜索提示更新为「搜索标题或内容…」 |

### 验证（curl 实测）

- `/console/tickets?warning=1` → 只显示临期工单，按钮正常渲染 ✅
- 知识库搜内容关键词「清理」（标题无此词）→ 命中文章 ✅

---

## 2026-09-08 — v2.1.3：客户档案售后时间快捷调整

> 测试：199 → **202** 用例全通过（+3）

### 新功能

客户详情页「产品与售后」卡片新增**售后时间调整**区：

| 操作 | 说明 |
|---|---|
| +1 年 / +6 个月 / +30 天 | 在现有售后到期时间上延长（未设置时以今天为基准） |
| 按登记+保修期重算 | 用 登记时间 + 产品保修天数 重算（需关联产品且已填登记时间） |
| 设为该日期 | 手动输入日期覆盖（不能早于今天） |

后端路由 `POST console/customers/{customer}/warranty`（模块权限 customers），操作成功返回详情页并提示新日期。

### 验证（curl 实测）

- 客户 2027-01-08 → 点 +1 年 → **2028-01-08** ✅
- 设为 2028-06-30 → 生效 ✅（已恢复原值）
- 新增 3 个 Feature 测试（extend_1y / recalc / set）全过 ✅

---

## 2026-09-08 — v2.1.2：工单模块全面接入后台自定义路径（ADMIN_URL）

> 测试：199 → **199** 用例全通过（更新 1 个断言适配新行为）

### 问题

上一版修复后，后台菜单除「工单」外都带 `/console` 前缀——因为 `tickets.*` 路由是**全局组**（客户门户共用），菜单指向它就没前缀；且视图/通知/搜索内的工单链接也硬编码 `route('tickets.*')` 生成无前缀 URL。

### 修复

| 项 | 改动 |
|---|---|
| admin 组镜像工单路由 | `console/tickets*` → 同一控制器，独立路由名 `admin.tickets.*`（不覆盖客户端的 `tickets.*`） |
| `ticket_route()` helper | 按角色选择路由：客服 → `admin.tickets.*`（带前缀），客户 → `tickets.*`；支持 `['for' => User]` / `['for_role' => 'agent']` 指定接收者（通知链接用） |
| 视图替换 | 工单列表/详情/创建/仪表盘/客户详情/搜索结果/最近工单/布局"新建工单"共 8 个视图的 `route('tickets.*')` → `ticket_route()` |
| 代码替换 | TicketController / TicketApiController / TicketService(@提及) / SearchService / SupportScanDaily(SLA 通知) 的通知与跳转链接全部按接收者角色生成 |
| 菜单 | agent 端「工单」→ `admin.tickets.index`；Seeder 清理旧 `agent/tickets.index` 残留 |

### 验证（curl 实测）

- `/console` 仪表盘快捷入口、`/console/tickets` 列表详情/认领/批量/新建按钮 → 全部 `/console/tickets*` ✅
- 客服搜索建议 → `/console/tickets/{id}`；客户不受影响（`/tickets`）✅

### 注意

新增 helper `app/helpers.php`（composer autoload files 注册），部署需 `composer dump-autoload`。

---

## 2026-09-08 — v2.1.1：后台自定义路径（ADMIN_URL）贯通修复

> 测试：196 → **199** 用例全通过（+3 新增）

### 问题

`ADMIN_URL` 只作用于业务路由（`/console/customers` 等），但 **登录后跳转 / 后台首页 / 侧边栏「仪表盘」仍指向全局 `/dashboard`**（无前缀），导致地址栏不显示自定义路径，隐蔽性打折扣。

### 修复

| 项 | 改动 |
|---|---|
| 后台首页路由 | admin 组新增 `GET /` → `admin.dashboard`（`/console`，role:agent 组内，admin 也放行） |
| 登录跳转 | 客服/管理员 → `admin.dashboard`（带前缀）；客户 → 全局 `dashboard`（不变）。覆盖 用户端密码/管理端/手机号/微信/API 全通道 |
| 根路径 `/` | 按角色分流：agent → `/console`，customer → `/dashboard` |
| 侧边栏 | agent 端「仪表盘」菜单改指 `admin.dashboard`；Logo 链接按角色指向对应首页 |
| 种子幂等 | MenuSeeder 自动清理旧 `agent/dashboard` 残留条目 |

### 验证（curl 实测）

- 管理员登录 → 302 `http://127.0.0.1:8000/console` ✅
- 登录态 `/console` → 200；侧边栏全部业务菜单带 `/console` 前缀 ✅
- 客户登录 → `/dashboard`（不变）✅

---

## 2026-09-08 — v2.1：P1 加固（队列化通知 + 登录审计 + SLA 临期预警 + 关闭禁回复 + CSAT 时效）

> 测试：188 → **196** 用例全通过（+8 新增，修复 1 个环境依赖用例）

### 一、性能：通知/邮件队列化

- `NotificationService` 邮件发送从同步改为**异步队列**（新增 `SendNotificationEmailJob`）
- 站点通知入库 + WS 推送仍即时，邮件走 `queue:work`（Redis 连接），失败自动重试 3 次
- `.env` 建议 `QUEUE_CONNECTION=redis`；生产需常驻 `queue:work`

### 二、安全：登录审计

- 新增 `login_audits` 表 + `AuditService`，记录**登录成功/失败**（账号/通道/IP/UA/原因）
- 覆盖 5 个登录入口：用户端密码、管理端、手机号验证码、微信扫码/绑定、API token
- 管理端新增「登录审计」页（系统管理 → 登录审计）：今日成功/失败统计、按账号/通道/结果筛选、分页

### 三、SLA 临期预警

- 每日巡检 `support:scan-daily` 新增「剩余 6 小时内」临期提醒（此前只有超时提醒）
- 通知负责人（未指派则全体客服），与超时提醒同链路

### 四、业务规则

- **已关闭工单禁止回复/备注**（终态，提示重新开单）；已解决工单客户仍可补充（自动重开，与既有设计一致）
- **CSAT 评分时效**：解决/关闭后 N 天内可评（默认 7 天，系统设置可调 1-90 天），超期前端隐藏表单 + 后端拒绝

### 五、部署注意事项

```bash
# 1. 数据库迁移（新增 login_audits 表）
php artisan migrate

# 2. 菜单种子（新增「登录审计」菜单项）
php artisan db:seed --class=MenuSeeder

# 3.（可选）队列连接切 Redis（如用数据库队列则跳过）
#    QUEUE_CONNECTION=redis
#    生产常驻 worker：php artisan queue:work --daemon
```

---

## 2026-08-31 — v2.0 大版本：菜单 DB 化 + 对外 API + 知识体系 + 视觉统一

> 测试：136 → **176** 用例全通过（+40）

### 一、新功能（P2 backlog 全部落地）

| 功能 | 说明 | 入口 |
|---|---|---|
| **侧边栏菜单 DB 化** | menus 表驱动，后台可增删改/排序/启停/分组/权限绑定 | 系统管理 → 菜单管理 |
| **对外 API 层** | Sanctum token 认证，工单/产品/客户/通知 12 个接口 | `/api/*`（见 docs/api.md） |
| **知识库** | 分类 + 文章 CRUD，Markdown 阅读页，草稿/发布，浏览数 | 客服工具 → 知识库 |
| **工单标签** | 详情页打标签/移除（支持新建），列表页按标签筛选 | 工单详情 / 列表筛选 |
| **@提及** | 回复/备注输入 `@客服姓名` → 对方收通知 + 实时推送 | 工单回复/备注 |
| **工单自定义字段** | 管理员配置文本/数字/下拉/日期字段，建单必填校验，详情展示 | 系统管理 → 工单字段 |
| **工作量报表** | 客服排行新增 平均解决时长 / SLA 超时数 | 数据报表 |

### 二、视觉统一（认证 + 后台全局）

- 登录页、注册、忘记密码、重置密码、邮箱验证五页统一风格（渐变主按钮 + 图标输入框 + 语义徽标），并顺带汉化
- 仪表盘：顶部渐变欢迎横幅（问候语 + 日期 + 快捷按钮）、统计卡增强
- 工单列表/详情：面包屑页头、筛选容器、批量按钮渐变
- 全站：页面标题品牌竖条、主要操作按钮统一渐变
- 客户/产品/报表/用户/设置页按钮与统计徽标统一

### 三、修复与加固

- 组件文件 Blade 注释未剥离导致图标渲染为字面量（root cause：`{{--` 在 `@props` 之前不被剥离 + f-string 转义）
- 菜单管理表格 `section` 字段渲染丢失
- nav-icon 补 `star`/`list` 图标（菜单管理侧栏图标原回退 ticket）
- 后台 title 竖条、报表满意度卡统一 stat-card（新增 hint 属性）

### 二·补 2026-08-31 追加（ae1c5b2 起）

- API 扩展至 16 路由：知识库（分类/列表/详情，仅已发布）、标签列表、工单 tag 筛选/返回
- 重复工单识别：同用户 24h 同主题未关闭 → Web 黄条提示 / API duplicate 字段（不阻止）
- 路由分层重组、控制器拆分（TicketService/ReportService）、tickets 索引 + API 登录限流
- 数据填充覆盖标签/字段/知识库（幂等）；测试 136 → 188

### 四、部署注意事项（本次变更必须执行）

```bash
# 1. 数据库迁移（新增表：menus / personal_access_tokens / kb_categories / kb_articles / tags / ticket_tag / ticket_field_defs / ticket_field_values）
php artisan migrate

# 2. 菜单种子（幂等，含知识库/工单字段等 16 条菜单）
php artisan db:seed --class=MenuSeeder

# 3.（可选）生产配置参考
cp .env.production.example .env   # 并配置 ADMIN_URL / MAIL / 短信密钥
```

**API 层无额外部署**：token 表随 migrate 建立，接口即开即用（见 docs/api.md）。

### 五、已知边界（有意为之）

- 菜单条目存库，但**路由/权限仍在代码侧**：加新功能模块仍需写控制器+路由，再在菜单管理登记
- @提及匹配客服/管理员姓名（中文 2-8 字、英文 2-20 字符），客户同名不触发
- 自定义字段停用后：老工单值保留展示，新建工单不再渲染
- 知识库草稿仅作者/管理员可见
