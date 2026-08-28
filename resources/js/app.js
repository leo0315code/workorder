import Alpine from 'alpinejs';

window.Alpine = Alpine;

/**
 * 实时连接（GatewayWorker）
 * - 连接成功走 WebSocket 推送
 * - 连接失败/鉴权失败自动降级为轮询（由页面组件监听 ticket:fallback 事件）
 */
class TicketRealtime {
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

window.TicketRealtime = TicketRealtime;

Alpine.start();
