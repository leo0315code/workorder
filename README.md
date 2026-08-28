# 工单系统 (Ticket System)

基于 **Laravel 13 + MySQL + Tailwind CSS v4 + Alpine.js + GatewayWorker** 的全栈工单系统，含客户门户与客服/管理员后台，支持实时推送。

## 技术栈

| 层 | 选型 |
|---|---|
| 后端 | Laravel 13（PHP 8.3） |
| 数据库 | MySQL 8.0（`laravel_ticket`） |
| 前端 | Blade + Tailwind CSS v4 + Vite + Alpine.js |
| 实时 | GatewayWorker（`workerman/gateway-worker` + `gatewayclient`），端口 6001 / Register 1238 |
| 认证 | Laravel Breeze（Blade 栈，已汉化） |

## 功能

- **登录体系（用户端/管理端分离）**
  - 用户端 `/login`：**密码登录 / 手机号验证码登录 / 微信扫码登录** 三通道（Tab 切换）
  - 管理端 `/console/login`：仅客服/管理员可登录，客户账号被拒绝并提示；**后台前缀不暴露 /admin**，默认 `console`，可用 `ADMIN_URL` 环境变量改为任意隐蔽路径（如 `backstage`），所有 admin.* 路由名不变、页面链接自动适配
  - 手机号登录：验证码 5 分钟有效，未注册手机号自动创建客户账号；`SMS_DRIVER=demo` 时验证码直接返回前端/写日志（本地联调），生产接阿里云/腾讯云短信
  - 微信扫码：已配置 `WECHAT_APPID/SECRET` 走开放平台真实回调；未配置时前端展示「模拟微信扫码」按钮，本地可直接跑通 扫码→绑定→登录 全链路；首次扫码需绑定已有账号或注册新账号
- **认证与角色**：`customer`(客户) / `agent`(客服) / `admin`(管理员)
- **客户门户**：提交工单（分类/产品/优先级/附件）、我的工单、对话式回复时间线
- **客服后台**：工单列表（搜索/状态/优先级/分类/负责人/只看我的/未指派 筛选）、处理操作（状态/优先级/指派）、**内部备注**（客户不可见）、**批量指派/批量关闭**、SLA 超时标识、**导出 CSV**（当前筛选条件，UTF-8 BOM 直开 Excel）
- **快捷回复**：常用回复模板管理，回复框一键插入
- **操作日志**：创建/回复/备注/状态/优先级/指派变更全程留痕，详情页审计时间线（客户不可见）
- **站内通知**：顶栏铃铛（未读红点+下拉预览）、通知中心页；新工单/指派/新回复/状态变更自动通知相关人，并经 WebSocket 实时送达
- **客户档案增强**：客户详情页（档案+保修进度条+全部关联工单）、CSV 导出（当前筛选）、CSV 导入（按邮箱/公司+电话匹配，支持 Excel 日期序列号，命中更新/否则新建）
- **满意度评价（CSAT）**：工单解决/关闭后客户 1-5 星评分 + 留言，详情页展示，报表统计平均分与满意率
- **每日巡检命令** `support:scan-daily`：SLA 超时工单 → 通知负责人（未指派则全体客服）；售后临期/过期客户 → 通知管理员
- **工单自动分配**：仅分配给**当前在线**（已建立 WebSocket 连接）的客服，按「未完成工单数最少」优先（优先普通客服）；无人在线则进入待认领；可在系统设置开关
- **客服在线管理**：用户管理页显示每个客服的**实时在线状态**（绿点）；管理员可**手动置为离线**（请假/挂机不参与自动分配，并强制断开其连接）或一键恢复
- **系统设置页**（管理端）：系统名称、自动分配开关、各优先级 SLA 时限、**工作时间**（开关 + 时段 + 工作日多选）；运行时保存即时生效（`settings` 表）；另展示实时服务/后台前缀/短信/微信通道运行状态
- **工作时间限制**：非工作时间客户不能提交工单（页面提示 + 后端拦截），客服不受限
- **列表实时与兜底**：所有登录页面建立全局 WebSocket 连接（在线状态 + 通知/工单实时），工单列表页新工单实时提示；WS 掉线自动降级为 20s 轮询 `/tickets/changes`
- **管理后台**：客户档案（绑定注册账号 + 产品保修期自动计算售后到期）、产品管理、分类管理、用户角色管理
- **数据报表**：近 7/30/90 天每日新增趋势、客服处理排行（处理数/回复数/平均首次响应时长）、状态/优先级/分类分布、满意度
- **实时推送**：新回复 / 状态变更 / 新工单 / 新通知即时送达（WS 不可用时自动降级为 8s 轮询）
- **仪表盘**：状态/优先级/分类分布、今日解决、SLA 超时、最近工单

## 快速开始

```bash
# 1. 依赖与环境
composer install
npm install

# 2. 数据库（MySQL 8）
#    .env: DB_DATABASE=laravel_ticket, DB_USERNAME=root, DB_PASSWORD=root
php artisan migrate --seed     # 含演示数据

# 3. 前端资源
npm run build                  # 开发时用 npm run dev

# 4. 存储链接（附件）
php artisan storage:link

# 5. 启动实时服务（GatewayWorker）
php artisan ws:start           # 停止: php artisan ws:stop
#    或前台运行: php websocket/start.php start

# 6. 启动 Web
php artisan serve
# 访问 http://127.0.0.1:8000
```

## 演示账号（密码均为 `password`）

| 角色 | 邮箱 | 权限 |
|---|---|---|
| 管理员 | admin@example.com | 全部 + 用户管理 |
| 客服 | agent@example.com / agent2@example.com | 工单处理、客户/产品/分类 |
| 客户 | customer@example.com / customer2@example.com | 提交与跟进自己的工单 |

## 项目结构（关键）

```
app/
  Console/Commands/WsStart.php, WsStop.php   # ws:start / ws:stop
  Http/Controllers/                          # Ticket/Customer/Product/Category/User/Dashboard
  Http/Middleware/EnsureRole.php             # role 中间件
  Models/                                    # User/Category/Product/Customer/Ticket/TicketReply/Attachment
  Services/WebSocketService.php              # 服务端推送封装（GatewayClient）
  Ws/Events.php                              # GatewayWorker 业务事件（鉴权/加房间）
websocket/
  start.php                  # Unix/Linux 组合启动（多进程，支持 -d）
  start_register.php         # Register 服务（可独立运行，Windows 用）
  start_gateway.php          # Gateway 服务（可独立运行，Windows 用）
  start_business.php         # BusinessWorker 服务（可独立运行，Windows 用）
  bootstrap.php              # 共享引导（autoload + Laravel 容器）
  start_win.bat              # Windows 一键启停批处理
database/seeders/DatabaseSeeder.php          # 演示数据
resources/views/                             # Blade 视图（全宽布局 + dark 模式）
```

## 实时机制

1. 页面渲染时用 `WebSocketService::signature(uid, rooms)` 生成 HMAC 鉴权 token，随 WS 配置注入前端。
2. 前端 `TicketRealtime`（`resources/js/app.js`）连接 `ws://127.0.0.1:6001`，发送 `{type:'auth', uid, token, rooms}`。
3. `Events::onMessage` 校验签名 → `bindUid` + `joinGroup('ticket.{id}')`。
4. 回复/状态变更时控制器调用 `WebSocketService::pushToRoom('ticket.{id}', ...)` 推送给房间内在线客户端；列表页另订阅 `ticket.all`。
5. WS 不可用（未启动服务/连接失败）时，页面自动启用 8 秒轮询接口 `/tickets/{id}/replies?after={lastId}` 兜底。

## 常见问题

- **端口被占**：`php websocket/start.php stop` 后重试；Register 默认 1238，Gateway 6001，可在 `.env` 中改。
- **WS_SECRET 变更**：改 `.env` 后需同时重启 GatewayWorker，否则前端鉴权会失败（前端 token 由 Laravel 按同一 secret 生成）。
- **附件上传**：默认存 `storage/app/public/tickets`，需 `php artisan storage:link`。

## Windows 服务器部署

项目整体兼容 Windows（Laravel 本身跨平台），实时服务（Workerman 5）在 Windows 上以**单进程前台模式**运行，自动适配：

### 1. 环境准备
- 安装 **PHP 8.1+**（建议 8.3，需 `pdo_mysql`、`openssl`、`mbstring`、`fileinfo` 扩展）并加入 PATH
- 安装 [Composer](https://getcomposer.org/)、Node.js 18+（仅构建前端资源用）
- 安装 MySQL 8（或使用 SQL Server/现有 MySQL，改 `.env` 即可）

### 2. 初始化（与 Linux 一致）
```bat
composer install
copy .env.example .env      rem 修改 DB_* / WS_SECRET
php artisan key:generate
php artisan migrate --seed
npm install && npm run build
php artisan storage:link
```

### 3. 启动实时服务（Windows）
```bat
php artisan ws:start
```
或使用批处理助手：`websocket\start_win.bat start`（停止 `stop`，查看 `status`）。

> Windows 下 Workerman **不支持 `-d` 守护与多进程**，命令会自动以单进程模式分别拉起
> Register / Gateway / BusinessWorker 三个进程，PID 记录在 `storage/app/websocket.pid`。

### 4. Web 服务
- **Nginx for Windows**：站点根目录指向 `public`，配置伪静态（`try_files $uri $uri/ /index.php?$query_string;`），PHP 用 FastCGI（php-cgi）。
- **IIS**：站点物理路径指向 `public`，安装 URL Rewrite 模块，导入以下规则：
  ```xml
  <rewrite>
    <rules>
      <rule name="Laravel" stopProcessing="true">
        <match url="^(.*)$" ignoreCase="false" />
        <conditions>
          <add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true" />
          <add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true" />
        </conditions>
        <action type="Rewrite" url="index.php" />
      </rule>
    </rules>
  </rewrite>
  ```
  PHP 通过 FastCGI 模块（php-cgi.exe）处理。

### 5. 开机自启（实时服务）
- 「任务计划程序」新建任务 → 触发器「计算机启动时」→ 操作 `start_win.bat start`（起始目录设为项目根）。
- 或在服务管理器注册 `php artisan ws:start` 的包装服务。

### 6. Windows 注意事项
- 单进程模式吞吐低于 Linux 多进程，生产高并发建议部署到 Linux / WSL2 / Docker。
- 附件路径、日志路径均为相对路径，无平台差异；`storage` 目录需保持可写。
- 开发调试时保持 `php artisan serve` 或上述 Web 服务运行，前端已内置 WS 不可用时的轮询兜底。

## 定时任务（SLA / 售后提醒）

每日巡检命令会自动扫描 **SLA 超时工单**（通知负责人/全体客服）与**售后临期、过期客户**（通知管理员），通过站内通知+实时推送送达：

- **Linux/macOS**（crontab）：
  ```cron
  0 9 * * * cd /path/to/laravel-ticket && php artisan support:scan-daily >> storage/logs/scheduler.log 2>&1
  ```
- **Windows**：任务计划程序，每日 9:00 运行 `php C:\path\to\laravel-ticket\artisan support:scan-daily`，起始目录设为项目根。
