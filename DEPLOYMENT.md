# 部署上线检查清单（Windows Server / Linux）

> 配套文件：`.env.production.example`。本地 `.env` 保持可开发状态，**不要**直接把本地 `.env` 带上生产。

## 1. 代码与构建

- [ ] `git clone` / `git pull` 最新代码到生产目录
- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `npm ci && npm run build`（生产不跑 `npm run dev`，HMR 仅限本地）
- [ ] 确认 `public/build` 存在（Vite 产物）

## 2. 环境变量（逐项核对）

| 项 | 生产要求 | 检查 |
|---|---|---|
| `APP_ENV` | `production` | ☐ |
| `APP_DEBUG` | `false` | ☐ |
| `APP_KEY` | 重新 `php artisan key:generate`，**不得沿用本地** | ☐ |
| `APP_URL` | 生产域名，与 nginx 一致 | ☐ |
| `DB_*` | 独立数据库账号，强密码 | ☐ |
| `MAIL_MAILER` | `smtp`（禁止 `log`） | ☐ |
| `SESSION_SECURE_COOKIE` | `true`（https 下） | ☐ |
| `ADMIN_URL` | 换随机前缀（默认 `console` 易枚举） | ☐ |
| `WS_SECRET` | 换随机长字符串，与 `websocket/start.php` 一致 | ☐ |
| `SMS_ALLOW_DEMO_CODE` | `false`（关万能验证码） | ☐ |

## 3. 数据库与迁移

- [ ] `php artisan migrate --force`
- [ ] 生产环境**不要**跑 `db:seed --class=DemoSeeder`（只跑必要的初始化）

## 4. 后台服务（Windows 计划任务 / Linux crontab）

- [ ] 每日巡检调度（本代码已注册，只需挂执行器）：
  - Linux：`* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1`
  - Windows：计划任务每分钟执行 `php artisan schedule:run`
  - 验证：`php artisan schedule:list` 应显示 `support:scan-daily`，日志在 `storage/logs/support-scan.log`
- [ ] 队列 worker（若启用邮件/通知入队）：
  - Linux：`php artisan queue:work --daemon` + supervisor
  - Windows：计划任务或 NSSM 注册为服务
- [ ] GatewayWorker 实时服务：`php artisan ws:start`（Windows 下用 `websocket/start.php start -d` 或注册为服务）

## 5. Web 服务器

- [ ] nginx 站点根目录指向 `public/`
- [ ] **不要把 `storage/app/private` 暴露到 URL**（附件是私有数据，nginx 不配置 `/storage` 别名）
- [ ] WSS 反向代理：`/ws` → `ws://127.0.0.1:6001`（需开启 `Upgrade`/`Connection` 头转发）
- [ ] 隐藏 `public/index.php` 痕迹、开启 TLS（强制 https + HSTS）

## 6. 上线自检

- [ ] `php artisan config:cache`、`php artisan route:cache`、`php artisan view:cache`
- [ ] 用普通客户账号登录 → 新建工单 → 上传附件 → 换另一账号确认**无法**访问该附件直链
- [ ] 客服登录 → 回复工单 → 客户收到站内通知；若配了 SMTP，确认收到邮件
- [ ] 系统设置 → 短信通道 → 发送测试短信，确认真实通道成功
- [ ] 无痕窗口访问 `/storage/...` 应 404/403（附件私有化生效）

## 7. 上线后监控

- [ ] 定期检查 `storage/logs/laravel.log` 与 `support-scan.log`
- [ ] 附件目录增长监控（`storage/app/private/tickets`）
- [ ] 数据库备份策略（每日全量 + binlog/增量）
