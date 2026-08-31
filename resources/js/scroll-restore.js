/**
 * 表单提交后恢复滚动位置
 *
 * - 只在用户主动提交表单（POST/PATCH/DELETE）时把 window.scrollY 记入 sessionStorage
 * - load 时若有记录才恢复（requestAnimationFrame），没有记录不干预，避免页面闪动
 * - history.scrollRestoration = 'auto'，让浏览器保留默认行为
 */
(function () {
    if ('scrollRestoration' in history) history.scrollRestoration = 'auto';

    // capture 阶段监听，确保拿到的是原生 submit（含按回车触发的提交）
    document.addEventListener('submit', function (e) {
        const m = ((e.target.method || 'get').toLowerCase());
        if (m !== 'get') {
            sessionStorage.setItem('wb-scroll-pos', String(window.scrollY));
        }
    }, true);

    window.addEventListener('load', function () {
        const y = sessionStorage.getItem('wb-scroll-pos');
        if (y === null) return; // 没有表单提交则不干预，避免闪动
        sessionStorage.removeItem('wb-scroll-pos');
        requestAnimationFrame(function () {
            window.scrollTo(0, parseInt(y, 10) || 0);
        });
    });
})();
