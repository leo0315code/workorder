# 部署上线检查清单（Windows Server / Linux）

> 配套文件：`.env.production.example`。本地 `.env` 保持可开发状态，**不要**直接把本地 `.env` 带上生产。

---

## 1. 代码与构建

- [ ] `git clone` / `git pull` 最新代码到生产目录
- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `npm ci && npm run build`（生产不跑 `npm run dev`，HMR 仅限本地）
- [ ] 确认 `public/build` 存在（Vite 产物）

---

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

> 生产模板见 `.env.production.example`，包含短信/微信/邮件完整占位。修改后执行 `php artisan config:cache`。

---

## 3. 数据库与迁移

- [ ] `php artisan migrate --force`
- [ ] 生产环境**不要**跑 `db:seed --class=DemoSeeder`（只跑必要的初始化）
- [ ] 备份：`mysqldump -u<user> -p <db> > backup_$(date +%F).sql`

---

## 4. 后台服务（Windows 计划任务 / Linux crontab / supervisor）

### 4.1 每日巡检调度（必须挂执行器）

- **Linux**：`crontab -e` 加一行
  ```cron
  * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
  ```
- **Windows**：任务计划程序 → 每分钟执行 `php artisan schedule:run`（起始目录设为项目根）
- 验证：`php artisan schedule:list` 应显示 `support:scan-daily`，日志在 `storage/logs/support-scan.log`

### 4.2 队列 worker（必须常驻，通知邮件已队列化，不跑会积压）

- **Linux（推荐 supervisor）**：新建 `/etc/supervisor/conf.d/laravel-worker.conf`
  ```ini
  [program:laravel-worker]
  process_name=%(program_name)s_%(process_num)02d
  command=php /path/to/app/artisan queue:work --sleep=3 --tries=3 --max-time=3600
  directory=/path/to/app
  autostart=true
  autorestart=true
  stopasgroup=true
  numprocs=2
  redirect_stderr=true
  stdout_logfile=/path/to/app/storage/logs/queue-worker.log
  ```
  ```bash
  supervisorctl reread && supervisorctl update && supervisorctl status
  ```
- **Linux/macOS 简易版**：`php artisan ws:queue start`（后台守护，日志 `storage/logs/queue-worker.log`；停止 `ws:queue stop`）
- **Windows（NSSM 注册为服务）**：
  ```bat
  nssm install laravel-worker "C:\path\php\php.exe" "C:\path\app\artisan" queue:work --sleep=3 --tries=3
  nssm set laravel-worker AppDirectory "C:\path\app"
  nssm start laravel-worker
  ```
- 验证：`php artisan ws:queue status` 显示「运行中」；`redis-cli LLEN`（若用 Redis 队列）不应持续增长

### 4.3 GatewayWorker 实时服务（必须常驻）

- **Linux（推荐 supervisor）**：新建 `/etc/supervisor/conf.d/gateway-worker.conf`
  ```ini
  [program:gateway-worker]
  command=php /path/to/app/websocket/start.php start
  directory=/path/to/app
  autostart=true
  autorestart=true
  stopasgroup=true
  redirect_stderr=true
  stdout_logfile=/path/to/app/storage/logs/websocket.log
  ```
- **Linux/macOS 简易版**：`php artisan ws:start`（停止 `ws:stop`）
- **Windows**：`websocket/start.php start -d`（或 NSSM 注册，`nssm install gateway-worker "C:\path\php\php.exe" "C:\path\app\websocket\start.php" start`）
- 验证：`lsof -i :6001 | grep LISTEN`（Windows 用 `netstat -ano | findstr 6001`）

---

## 5. Web 服务器（nginx 完整示例，Linux）

> Windows 同理，路径换成盘符路径。以下为 https + Laravel + WSS 一体配置：

```nginx
server {
    listen 80;
    server_name your-domain.example.com;
    return 301 https://$host$request_uri;   # 强制 https
}

server {
    listen 443 ssl;
    http2 on;
    server_name your-domain.example.com;

    # TLS 证书（Let's Encrypt 或云厂商）
    ssl_certificate     /etc/nginx/ssl/your-domain.example.com.crt;
    ssl_certificate_key /etc/nginx/ssl/your-domain.example.com.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers on;

    # 安全响应头
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;

    root /path/to/app/public;
    index index.php;

    charset utf-8;
    client_max_body_size 20m;              # 附件上传（10MB/个 × 5）

    # Laravel 入口
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;   # 或 127.0.0.1:9000
        fastcgi_read_timeout 300;
    }

    # 静态资源缓存
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff2?)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # 附件私有盘：明确禁止暴露（security 强制项）
    location ~ ^/storage/ {
        deny all;
        return 404;
    }

    # WSS 反向代理 → GatewayWorker 6001（保持明文）
    location /ws {
        proxy_pass http://127.0.0.1:6001;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_read_timeout 3600s;
        proxy_send_timeout 3600s;
    }

    # 隐藏 index.php 痕迹
    location ~ /\. {
        deny all;
    }
}
```

- [ ] `.env` 设 `WS_PROXY_PATH=ws`（前端自动生成 `wss://域名/ws`）
- [ ] 附件私有：nginx **不配置** `/storage` 别名（上面已 deny）

### 5.1 本地虚拟域名（ServBay，开发参考）

- 站点：`https://workorder.test`（root=`项目/public`，HTTPS 证书自动签发）
- `.env`：`APP_URL=https://workorder.test`、`SESSION_DOMAIN=.workorder.test`、`VITE_WS_URL=`（留空）、`WS_PROXY_PATH=ws`
- hosts：`127.0.0.1 workorder.test`（ServBay 已自动配置）
- ⚠️ ServBay 的 vhosts/*.conf 由面板自动生成，**重新生成站点会覆盖手动加的 `/ws` 块**，需重新补上（备份：`workorder.test.conf.bak-20260909`）

---

## 6. 上线自检

- [ ] `php artisan config:cache`、`php artisan route:cache`、`php artisan view:cache`
- [ ] 用普通客户账号登录 → 新建工单 → 上传附件 → 换另一账号确认**无法**访问该附件直链
- [ ] 客服登录 → 回复工单 → 客户收到站内通知；若配了 SMTP，确认收到邮件
- [ ] 系统设置 → 短信通道 → 发送测试短信，确认真实通道成功
- [ ] 无痕窗口访问 `/storage/...` 应 404/403（附件私有化生效）
- [ ] 浏览器控制台确认 WebSocket 连接成功（`wss://域名/ws`），Network 无 `ws` 报错

---

## 7. 发布流程（更新代码）

```bash
# Linux
cd /path/to/app
git pull
composer install --no-dev --optimize-autoloader   # 有依赖变化时
npm ci && npm run build                           # 有前端变化时
php artisan migrate --force                        # 有迁移时
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart                          # 重启 worker 加载新代码
php artisan ws:stop && php artisan ws:start        # 如网关逻辑有变（一般无需）
```

> 升级前务必备份数据库；大版本升级建议先在一台测试机跑通再上生产。

---

## 8. 上线后监控

- [ ] 定期检查 `storage/logs/laravel.log` 与 `support-scan.log`
- [ ] 附件目录增长监控（`storage/app/private/tickets`）
- [ ] 数据库备份策略（每日全量 + binlog/增量）
- [ ] 服务健康：`php artisan ws:queue status` + `lsof -i :6001`（或 supervisorctl status）

---

## 9. 故障排查速查

| 症状 | 排查 |
|---|---|
| 页面能开但**实时消息不推送** | ① `lsof -i :6001` 确认 GatewayWorker 在跑 ② 浏览器 Network 看 `wss://域名/ws` 是否 101 ③ 检查 nginx `/ws` 块是否被覆盖 ④ 确认 `.env` 的 `WS_PROXY_PATH=ws` 且 `VITE_WS_URL` 为空 |
| 邮件没收到 | ① `php artisan ws:queue status` worker 是否在跑 ② `storage/logs/queue-worker.log` ③ 系统设置 email_notify_enabled 是否开启 ④ 用户通知偏好是否关闭了邮件 |
| 登录后跳 404 | `ADMIN_URL` 与 nginx/缓存不一致 → `php artisan config:clear` 后重新 `config:cache` |
| 附件上传失败 | nginx `client_max_body_size`（≥20m）、`storage/app/private` 写权限 |
| 502 Bad Gateway | PHP-FPM 未启动或 `fastcgi_pass` 配错、`public/index.php` 权限 |
| 队列积压持续增长 | worker 没跑或已死 → 重启 supervisor/ws:queue，看 `failed_jobs` 表 |
