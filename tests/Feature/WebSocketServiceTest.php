<?php

namespace Tests\Feature;

use App\Services\WebSocketService;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * WebSocketServiceTest：WebSocket 服务
 * - signature：HMAC 签名确定性 + rooms 排序无关性 + 非法 rooms 过滤（与服务端 Events::signature 一致）
 * - frontendWsUrl：VITE_WS_URL 显式优先 / 代理路径拼接 / 端口直连（request host/secure 通过注入 Request 控制）
 */
class WebSocketServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SERVER['VITE_WS_URL'], $_SERVER['WS_PROXY_PATH']);
        parent::tearDown();
    }

    private function mockRequest(string $host, bool $secure): void
    {
        $request = Request::create('https://'.$host.'/', 'GET');
        $request->server->set('HTTPS', $secure ? 'on' : 'off');

        $this->app->instance('request', $request);
    }

    // -------------------------------------------------------------------------
    // signature
    // -------------------------------------------------------------------------

    public function test_signature_is_deterministic_and_order_independent(): void
    {
        $a = WebSocketService::signature(1, ['ticket.1', 'ticket.2']);
        $b = WebSocketService::signature(1, ['ticket.2', 'ticket.1']);

        $this->assertSame($a, $b);
        $this->assertSame(64, strlen($a)); // sha256 hex
    }

    public function test_signature_changes_with_uid_and_secret(): void
    {
        config(['websocket.secret' => 'secret-a']);
        $a = WebSocketService::signature(1, ['ticket.1']);
        config(['websocket.secret' => 'secret-b']);
        $b = WebSocketService::signature(1, ['ticket.1']);

        $this->assertNotSame($a, $b);
    }

    public function test_signature_filters_non_string_rooms(): void
    {
        $withInt = WebSocketService::signature(1, ['ticket.1', 123, ['x']]);
        $clean = WebSocketService::signature(1, ['ticket.1']);

        $this->assertSame($clean, $withInt);
    }

    public function test_signature_matches_server_side_events(): void
    {
        // 服务端 App\Ws\Events::signature 与 Laravel 侧必须一致（否则鉴权失败）
        config(['websocket.secret' => 'shared-secret']);
        $expected = \hash_hmac('sha256', '7|ticket.1,ticket.3', 'shared-secret');

        $this->assertSame($expected, WebSocketService::signature(7, ['ticket.3', 'ticket.1']));
    }

    // -------------------------------------------------------------------------
    // frontendWsUrl
    // -------------------------------------------------------------------------

    public function test_frontend_ws_url_prefers_explicit_vite_url(): void
    {
        $_SERVER['VITE_WS_URL'] = 'wss://ws.example.com/socket';
        config(['websocket.gateway_listen' => '127.0.0.1:6001']);

        $this->assertSame('wss://ws.example.com/socket', WebSocketService::frontendWsUrl());
    }

    public function test_frontend_ws_url_uses_proxy_path(): void
    {
        unset($_SERVER['VITE_WS_URL']);
        $_SERVER['WS_PROXY_PATH'] = 'ws';
        config(['websocket.gateway_listen' => '127.0.0.1:6001']);
        $this->mockRequest('example.com', false);

        $this->assertSame('ws://example.com/ws', WebSocketService::frontendWsUrl());
    }

    public function test_frontend_ws_url_uses_wss_with_proxy_on_https(): void
    {
        unset($_SERVER['VITE_WS_URL']);
        $_SERVER['WS_PROXY_PATH'] = 'ws';
        config(['websocket.gateway_listen' => '127.0.0.1:6001']);
        $this->mockRequest('example.com', true);

        $this->assertSame('wss://example.com/ws', WebSocketService::frontendWsUrl());
    }

    public function test_frontend_ws_url_falls_back_to_direct_port(): void
    {
        // .env 已配 WS_PROXY_PATH=ws，需显式置空覆盖
        $_SERVER['VITE_WS_URL'] = '';
        $_SERVER['WS_PROXY_PATH'] = '';
        config(['websocket.gateway_listen' => '0.0.0.0:6001']);
        $this->mockRequest('example.com', false);

        $this->assertSame('ws://example.com:6001', WebSocketService::frontendWsUrl());
    }
}
