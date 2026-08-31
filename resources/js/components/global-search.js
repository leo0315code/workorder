/**
 * 顶部全局搜索组件（Alpine x-data="globalSearch()"）
 *
 * - 输入防抖 250ms 调 /search/suggest 下拉建议（工单/客户/产品）
 * - 回车直达结果页 /search?q=
 * - 路由地址来自布局注入的 window.__app.routes（服务端生成，避免硬编码）
 */
window.globalSearch = function () {
    return {
        q: '',
        items: [],
        open: false,

        suggest() {
            if (this.q.trim().length < 1) {
                this.items = [];
                this.open = false;
                return;
            }
            const url = (window.__app?.routes?.searchSuggest || '/search/suggest')
                .replace('__Q__', encodeURIComponent(this.q));

            fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    cache: 'no-store',
                })
                .then((r) => (r.ok ? r.json() : { items: [] }))
                .then((d) => {
                    this.items = d.items || [];
                    this.open = this.items.length > 0;
                })
                .catch(() => { this.items = []; });
        },

        go() {
            if (this.q.trim() === '') return;
            window.location.href = '/search?q=' + encodeURIComponent(this.q.trim());
        },
    };
};
