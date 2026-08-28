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

    // WSS（WebSocket over TLS）：生产 HTTPS 环境必须启用，否则浏览器拦截 ws:// 混合内容
    // 启用后同时监听 ws:6001（内网/联调）与 wss:6002（HTTPS 页面）
    'ssl' => [
        'enabled' => env('WS_SSL_ENABLED', false),
        'listen' => env('WS_SSL_LISTEN', '0.0.0.0:6002'),
        // 证书/私钥路径（生产用正规证书，本地可用 storage/websocket-ssl/ 下自签证书联调）
        'cert' => env('WS_SSL_CERT', base_path('storage/websocket-ssl/server.crt')),
        'key' => env('WS_SSL_KEY', base_path('storage/websocket-ssl/server.key')),
        'ca' => env('WS_SSL_CA', ''),
        'verify_peer' => env('WS_SSL_VERIFY_PEER', false),
    ],
];
