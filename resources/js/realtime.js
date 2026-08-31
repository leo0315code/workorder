/**
 * GatewayWorker 实时连接（TicketRealtime）
 *
 * - 连接成功走 WebSocket 推送（auth 握手携带 uid/token/rooms）
 * - 连接失败 / 鉴权失败 / 4 秒未握手成功 → 自动降级为轮询
 *   （页面组件监听 ticket:fallback 事件切换）
 *
 * 供页面 Alpine 组件（工单列表/详情）通过 window.TicketRealtime 使用，
 * 全局自动连接由 initRealtime() 读取布局注入的 window.__app.ws 触发。
 */
export class TicketRealtime {
    constructor(config) {
        this.config = config;
        this.ws = null;
        this.connected = false;
        this.reconnectTimer = null;
        this.fallbackStarted = false;
        this.connect();
    }

    connect() {
        const { wsUrl, uid, token, rooms } = this.config;

        let socket;
        try {
            socket = new WebSocket(wsUrl);
        } catch (e) {
            this.startFallback();
            return;
        }
        this.ws = socket;

        socket.onopen = () => {
            socket.send(JSON.stringify({ type: 'auth', uid, token, rooms }));
        };

        socket.onmessage = (e) => {
            let msg;
            try {
                msg = JSON.parse(e.data);
            } catch (err) {
                return;
            }
            if (msg.type === 'auth_ok') {
                this.connected = true;
                this.emit('ticket:status', { connected: true });
            } else if (msg.type === 'auth_fail') {
                this.emit('ticket:status', { connected: false });
                try { socket.close(); } catch (err) { /* noop */ }
                this.startFallback();
            } else if (msg.type === 'ping') {
                socket.send(JSON.stringify({ type: 'pong' }));
            } else {
                this.emit('ticket:event', msg);
            }
        };

        socket.onerror = () => {};

        socket.onclose = () => {
            this.connected = false;
            this.emit('ticket:status', { connected: false });
            this.scheduleReconnect();
        };

        // 4 秒内未鉴权成功 → 降级轮询
        setTimeout(() => {
            if (!this.connected) this.startFallback();
        }, 4000);
    }

    scheduleReconnect() {
        clearTimeout(this.reconnectTimer);
        this.reconnectTimer = setTimeout(() => this.connect(), 5000);
    }

    startFallback() {
        if (this.fallbackStarted) return;
        this.fallbackStarted = true;
        this.emit('ticket:fallback', {});
    }

    emit(name, detail) {
        window.dispatchEvent(new CustomEvent(name, { detail }));
    }

    static init(el, config) {
        return new TicketRealtime(config);
    }
}

/**
 * 布局注入的配置（window.__app.ws，仅登录用户存在）。
 * 自动建立全局连接：保持在线状态（供自动分配判断）+ 铃铛/列表实时。
 */
export function initRealtime() {
    if (! window.__app || ! window.__app.ws) return;

    try {
        window.__ticketRT = new TicketRealtime(window.__app.ws);
    } catch (e) { /* 实时不可用则仅轮询兜底 */ }
}
