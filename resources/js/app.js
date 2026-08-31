/**
 * 前端入口（Vite 打包）
 *
 * 职责：引入 Alpine 与各全局组件/工具，统一挂载到 window 后启动。
 * 所有模块均为带 hash 的构建产物，改动后浏览器自动加载新文件，无需手动清缓存。
 *
 * 注意：
 * - <head> 内仍保留一小段同步主题脚本（防首屏暗色闪烁 FOUC），不在此打包
 * - 服务端动态值（路由/WS 配置）由布局注入 window.__app，模块只读配置不硬编码
 */
import Alpine from 'alpinejs';

import { TicketRealtime, initRealtime } from './realtime';
import './components/global-search';
import './components/notification-bell';
import './scroll-restore';

window.Alpine = Alpine;
// 工单列表/详情页 Alpine 组件内会 new TicketRealtime(config)，必须挂在全局
window.TicketRealtime = TicketRealtime;

// 全局实时连接（仅登录用户；布局注入 window.__app.ws）
initRealtime();

Alpine.start();
