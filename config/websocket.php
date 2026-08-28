<?php

return [
    // 服务端与客户端鉴权共享密钥（HMAC 签名）
    'secret' => env('WS_SECRET', 'change-me'),

    // GatewayWorker 服务监听地址（浏览器连接）
    'gateway_listen' => env('WS_GATEWAY_LISTEN', '0.0.0.0:6001'),
    'gateway_lan_ip' => env('WS_GATEWAY_LAN_IP', '127.0.0.1'),
    'gateway_start_port' => env('WS_GATEWAY_START_PORT', 2900),
    'register_listen' => env('WS_REGISTER_LISTEN', '0.0.0.0:1238'),
    'processes' => env('WS_PROCESSES', 4),

    // Laravel 侧 GatewayClient 连接 Register 服务（用于服务端推送）
    'gateway_client_register' => env('WS_CLIENT_REGISTER', '127.0.0.1:1238'),

    // WSS（WebSocket over TLS）：统一走 Web 服务器（Nginx/Apache）反向代理终结 TLS，
    // 前端配置 WS_PROXY_PATH=/ws 后自动使用 wss://域名/ws；GatewayWorker 保持明文监听
];
