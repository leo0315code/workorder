/**
 * 通知铃铛组件（Alpine x-data="notificationBell()"）
 *
 * - unread 角标：进入页面刷新 + WS 实时推送 +1
 * - 下拉列表：最近 8 条（link 为应用内相对路径，点击跳转）
 * - markRead / markAllRead 走 POST + CSRF
 * - 路由地址全部来自布局注入的 window.__app.routes（服务端生成）
 */
window.notificationBell = function () {
    const R = window.__app?.routes || {};

    return {
        open: false,
        unread: 0,
        items: [],

        formatTime(s) {
            if (! s) return '';
            const d = new Date(s.replace(/-/g, '/'));
            if (isNaN(d)) return '';
            const diff = Math.floor((Date.now() - d.getTime()) / 1000);
            if (diff < 60) return '刚刚';
            if (diff < 3600) return Math.floor(diff / 60) + ' 分钟前';
            if (diff < 86400) return Math.floor(diff / 3600) + ' 小时前';
            if (diff < 86400 * 7) return Math.floor(diff / 86400) + ' 天前';
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return m + '-' + day;
        },

        init() {
            this.refresh();
        },

        refresh() {
            fetch(R.notificationsUnread || '/notifications/unread-count')
                .then((r) => r.json())
                .then((d) => { this.unread = d.count || 0; })
                .catch(() => {});
        },

        onEvent(msg) {
            // 实时推送的新通知
            if (msg.type === 'notification') {
                this.unread += 1;
                fetch(R.notificationsLatest || '/notifications/latest')
                    .then((r) => r.json())
                    .then((d) => { this.items = d.items || []; })
                    .catch(() => {});
            }
        },

        toggle() {
            this.open = !this.open;
            if (this.open && this.items.length === 0) {
                fetch(R.notificationsLatest || '/notifications/latest')
                    .then((r) => r.json())
                    .then((d) => { this.items = d.items || []; })
                    .catch(() => {});
            }
        },

        markAllRead() {
            fetch(R.notificationsReadAll || '/notifications/read-all', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                })
                .then(() => {
                    this.unread = 0;
                    this.items = this.items.map((n) => ({ ...n, is_read: true }));
                });
        },

        markRead(id) {
            const base = R.notificationsRead || '/notifications/__ID__/read';
            fetch(base.replace('__ID__', id), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                });
            this.unread = Math.max(0, this.unread - 1);
            this.items = this.items.map((n) => (n.id === id ? { ...n, is_read: true } : n));
        },

        openNotification(n) {
            // 先关下拉，再异步标已读，再导航（避免点击穿透/外点击关闭吞掉事件）
            const href = n.link || R.notificationsIndex || '/notifications';
            this.open = false;
            if (! n.is_read) {
                this.markRead(n.id);
            }
            // 用 rAF 保证下拉收起后再跳转，避免视觉抖动
            requestAnimationFrame(() => { window.location.href = href; });
        },
    };
};
