# 对外 API 对接文档（App / 小程序 / 第三方）

> 基地址：`https://<你的域名>/api`（本地开发：`http://127.0.0.1:8000/api`）
> 数据格式：JSON；编码：UTF-8

## 1. 快速开始

```bash
# 1. 登录拿 token
curl -X POST https://your-domain/api/auth/login \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"account":"13800138000","password":"your-password"}'

# 2. 后续请求带 Authorization 头
curl https://your-domain/api/tickets \
  -H "Authorization: Bearer <access_token>" \
  -H "Accept: application/json"
```

**鉴权方式**：请求头 `Authorization: Bearer <access_token>`。
token 由登录接口签发；登出后即失效（吊销）。

**错误响应约定**：

| HTTP 状态 | 含义 | 响应体 |
|---|---|---|
| 401 | 未登录 / token 无效/过期 | `{"message":"Unauthenticated."}` |
| 403 | 已登录但无权操作 | `{"message":"无权操作该工单"}` 等 |
| 422 | 参数校验失败 | `{"message":"...","errors":{"字段":["错误信息"]}}` |
| 201 | 创建成功 | 见各接口 |

---

## 2. 认证

### 2.1 登录（POST /auth/login）

账号支持 **手机号 或 邮箱** 二选一（同一字段 `account`）。

请求：
```json
{
  "account": "13800138000",     // 必填：手机号 或 邮箱
  "password": "your-password",  // 必填：≥8 位
  "device": "iOS-16/小程序"      // 选填：token 名称，便于后台区分设备
}
```

响应（200）：
```json
{
  "access_token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "token_type": "Bearer",
  "user": {
    "id": 3,
    "name": "张三",
    "role": "customer",          // customer | agent | admin
    "phone": "13800138000",
    "email": "zhangsan@example.com",
    "avatar": null
  }
}
```

### 2.2 登出（POST /auth/logout）

吊销当前 token。之后该 token 立即失效。

```bash
curl -X POST https://your-domain/api/auth/logout \
  -H "Authorization: Bearer <token>" -H "Accept: application/json"
```
响应（200）：`{"message":"已登出"}`

### 2.3 当前用户（GET /me）

返回与登录响应相同的 `user` 结构。

---

## 3. 工单

> 权限规则：
> - **客户**：只能查看/回复自己的工单
> - **客服 / 管理员**：可查看全部工单

### 3.1 工单列表（GET /tickets）

查询参数：

| 参数 | 类型 | 默认 | 说明 |
|---|---|---|---|
| `status` | string | `open` | `open`=待处理中 / `resolved`=已解决、关闭 / `all`=全部 |
| `page` | int | 1 | 页码 |
| `per_page` | int | 15 | 每页条数 |

响应（200）：
```json
{
  "items": [
    {
      "id": 12,
      "no": "TK-260831-0005",
      "subject": "电脑无法开机",
      "description": "按下电源键无反应",
      "status": "open",
      "priority": "high",
      "category": "硬件故障",
      "product": "工控主机",
      "assignee": "王客服",
      "sla_due_at": "2026-09-02 09:00:00",
      "created_at": "2026-08-31 09:00:00",
      "updated_at": "2026-08-31 09:05:00"
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 3,
    "total": 42
  }
}
```

### 3.2 工单详情（GET /tickets/{id}）

响应（200）：
```json
{
  "ticket": {
    "id": 12,
    "no": "TK-260831-0005",
    "subject": "电脑无法开机",
    "description": "按下电源键无反应",
    "status": "open",
    "priority": "high",
    "category": "硬件故障",
    "product": "工控主机",
    "assignee": "王客服",
    "sla_due_at": "2026-09-02 09:00:00",
    "created_at": "2026-08-31 09:00:00",
    "updated_at": "2026-08-31 09:05:00"
  },
  "replies": [
    {
      "id": 8,
      "type": "reply",            // reply=对话消息；note=内部备注（客户看不到）
      "content": "我们正在检查，请稍候",
      "user": { "id": 2, "name": "王客服" },
      "created_at": "2026-08-31 09:10:00"
    }
  ]
}
```

> 客户视角：`replies` 只含 `type=reply`；客服视角额外包含内部备注。

### 3.3 创建工单（POST /tickets）

请求：
```json
{
  "subject": "电脑无法开机",      // 必填，≤255
  "description": "按下电源键无反应", // 必填，≤10000
  "priority": "high",             // 必填：low | normal | high | urgent
  "category_id": 1,               // 选填：工单分类
  "product_id": 2,                // 选填：关联产品
  "assignee_id": 5                // 选填：仅客服可指定；不填走自动分配
}
```

响应（201）：
```json
{
  "message": "工单已提交",
  "ticket": { "id": 13, "no": "TK-260831-0006", "subject": "电脑无法开机", "...": "同上结构" },
  "duplicate": {
    "ticket_id": 12,
    "no": "TK-260831-0005",
    "subject": "电脑无法开机"
  }
}
```

> `duplicate`：**重复工单识别**——同一用户近 24 小时内存在主题相同且未关闭的工单时返回其信息（不阻止创建），无重复时为 `null`。

> **注意**：
> - 客户在**非工作时间**提交会返回 422（工作时间在系统设置中配置，默认周一至周五 09:00-18:00）
> - 提交后自动计算 SLA 到期时间（按优先级），并实时推送给客服

### 3.4 回复工单（POST /tickets/{id}/replies）

请求：
```json
{
  "content": "我们正在检查，请稍候"   // 必填，≤10000
}
```

响应（201）：
```json
{
  "message": "回复成功",
  "reply": {
    "id": 9,
    "content": "我们正在检查，请稍候",
    "created_at": "2026-08-31 09:10:00"
  }
}
```

> 客服回复 → 通知客户；客户回复 → 通知工单负责人（未指派则通知全体客服）。

---

## 4. 基础数据

### 4.1 产品列表（GET /products）

登录即可调用（客户建单时选产品用）。仅返回启用中的产品。

响应（200）：
```json
{
  "items": [
    { "id": 1, "name": "工控主机", "sku": "IPC-01" }
  ]
}
```

### 4.2 标签列表（GET /tags）

登录即可调用（工单按标签筛选 / 展示用）。

响应（200）：
```json
{
  "items": [
    { "id": 1, "name": "高优先级", "color": "rose" }
  ]
}
```

> 工单列表支持 `tag_id` 参数筛选；工单列表/详情的 `ticket` 对象含 `tags` 数组。

### 4.3 客户列表（GET /customers）

**仅客服 / 管理员**可调用，客户调用返回 403。

响应（200）：
```json
{
  "items": [
    {
      "id": 3,
      "company": "某某医院",
      "contact_name": "王医生",
      "phone": "13900139001",
      "product": "工控主机"
    }
  ]
}
```

---

## 5. 知识库（App 端浏览）

> 仅返回**已发布**文章；草稿对客户端不可见（详情返回 404）。

### 5.1 分类列表（GET /kb/categories）

响应（200）：
```json
{
  "items": [
    { "id": 1, "name": "常见故障", "article_count": 3 }
  ]
}
```

### 5.2 文章列表（GET /kb/articles）

查询参数：`category_id`（按分类）、`q`（标题/内容关键词，URL 编码）、`page` / `per_page`。

响应（200）：
```json
{
  "items": [
    { "id": 1, "title": "登录提示验证码错误怎么办", "category": "常见故障", "views": 24, "updated_at": "2026-08-31" }
  ],
  "pagination": { "current_page": 1, "last_page": 1, "total": 3 }
}
```

### 5.3 文章详情（GET /kb/articles/{id}）

返回 Markdown 原文（`content`），前端自行渲染；浏览数 +1。

响应（200）：
```json
{
  "article": {
    "id": 1,
    "title": "登录提示验证码错误怎么办",
    "content": "# 登录提示验证码错误\n\n1. 清理浏览器缓存...",
    "category": "常见故障",
    "views": 25,
    "updated_at": "2026-08-31 09:10:00"
  }
}
```

---

## 6. 通知

### 5.1 我的通知（GET /notifications）

查询参数：`unread=1` 只看未读；`page` / `per_page`。

响应（200）：
```json
{
  "items": [
    {
      "id": 18,
      "title": "工单有新回复",
      "body": "TK-260831-0005 · 电脑无法开机",
      "link": "/tickets/12",
      "is_read": false,
      "created_at": "2026-08-31 09:10:00"
    }
  ],
  "pagination": { "current_page": 1, "last_page": 1, "total": 1 }
}
```

> `link` 为**应用内相对路径**，前端拼接自己的域名（如 `https://your-domain` + `/tickets/12`）。

### 5.2 未读数（GET /notifications/unread-count）

响应（200）：`{"count": 3}` —— App 角标轮询用。

### 5.3 标记已读（POST /notifications/{id}/read）

只能标记自己的通知，他人通知返回 403。
响应（200）：`{"message":"已读"}`

---

## 7. 状态与优先级枚举

| 字段 | 取值 | 含义 |
|---|---|---|
| status | `open` | 待处理 |
| status | `pending` | 待客户 |
| status | `in_progress` | 处理中 |
| status | `resolved` | 已解决 |
| status | `closed` | 已关闭 |
| priority | `low` / `normal` / `high` / `urgent` | 低 / 普通 / 高 / 紧急 |

---

## 8. 常见问题

**Q：token 过期怎么办？**
当前无过期时间（长期有效），登出后失效。如需短期 token 或刷新机制，可在 `AuthApiController::login` 中给 `createToken()` 传 `expiresAt`。

**Q：小程序里如何携带 token？**
请求头 `Authorization: Bearer <token>`。小程序 `wx.request` 中：
```js
header: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
```

**Q：404 / 500 怎么办？**
404 = 资源不存在（路由或 ID 错误）；500 = 服务端异常（检查 `storage/logs/laravel.log`）。
