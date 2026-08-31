<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('page_title', \App\Services\SettingService::siteName()) — {{ \App\Services\SettingService::siteName() }}</title>

        <!-- 暗色模式：class 驱动，跟随 localStorage / 系统 -->
        <script>
            (function () {
                var t = localStorage.getItem('theme') || 'system';
                var dark = t === 'dark' || (t === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                if (dark) document.documentElement.classList.add('dark');
                window.__theme = t;
            })();
        </script>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full font-sans antialiased text-gray-800 bg-gray-50 dark:bg-gray-950 dark:text-gray-100">
        <div x-data="{ sidebarOpen: false }" class="min-h-full">

            @php
                $user = auth()->user();
                // 管理端（agent/admin）显示完整后台菜单；客户仅门户菜单
                // 菜单项统一以数据驱动：label/route/icon/active 一次配好，遍历按权限过滤
                $navItems = $user && $user->isAgent() ? [
                    ['label' => '仪表盘',     'route' => 'dashboard',                     'icon' => 'dashboard', 'active' => request()->routeIs('dashboard')],
                    ['label' => '工单',       'route' => 'tickets.index',                'icon' => 'ticket',    'active' => request()->routeIs('tickets.*') && ! request()->routeIs('tickets.create')],
                    ['module' => 'customers',  'label' => '客户档案', 'route' => 'admin.customers.index',        'icon' => 'customer',  'active' => request()->routeIs('admin.customers.*')],
                    ['module' => 'products',   'label' => '产品管理', 'route' => 'admin.products.index',         'icon' => 'product',   'active' => request()->routeIs('admin.products.*')],
                    ['module' => 'categories', 'label' => '分类管理', 'route' => 'admin.categories.index',       'icon' => 'category',  'active' => request()->routeIs('admin.categories.*')],
                    ['module' => 'quick-replies', 'label' => '快捷回复', 'route' => 'admin.quick-replies.index', 'icon' => 'reply', 'active' => request()->routeIs('admin.quick-replies.*')],
                    ['module' => 'templates',  'label' => '工单模板', 'route' => 'admin.ticket-templates.index', 'icon' => 'ticket', 'active' => request()->routeIs('admin.ticket-templates.*')],
                    ['module' => 'reports',    'label' => '数据报表', 'route' => 'admin.reports',              'icon' => 'chart',     'active' => request()->routeIs('admin.reports')],
                ] : [
                    ['label' => '仪表盘',     'route' => 'dashboard',      'icon' => 'dashboard', 'active' => request()->routeIs('dashboard')],
                    ['label' => '我的工单',   'route' => 'tickets.index', 'icon' => 'ticket',    'active' => request()->routeIs('tickets.*') && ! request()->routeIs('tickets.create')],
                ];
                // 仅登录用户显示后台模块项；客户不被后台菜单骚扰
                $nav = array_values(array_filter($navItems, fn ($it) => $user && (! isset($it['module']) || $user->canAccessModule($it['module']))));
                // 管理员专属（用户/角色/设置）追加在末尾
                if ($user && $user->isAdmin()) {
                    $nav = array_merge($nav, [
                        ['label' => '用户管理', 'route' => 'admin.users.index',     'icon' => 'user',   'active' => request()->routeIs('admin.users.*')],
                        ['label' => '角色管理', 'route' => 'admin.agent-roles.index', 'icon' => 'shield', 'active' => request()->routeIs('admin.agent-roles.*')],
                        ['label' => '系统设置', 'route' => 'admin.settings',        'icon' => 'gear',   'active' => request()->routeIs('admin.settings')],
                    ]);
                }
            @endphp

            {{-- 侧边栏 --}}
            <div class="fixed inset-y-0 left-0 z-40 w-64 transform transition-transform lg:translate-x-0" x-bind:class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
                <div class="flex flex-col h-full bg-white border-r border-gray-200 dark:bg-gray-900 dark:border-gray-800">
                    <div class="flex items-center justify-between h-16 px-5 border-b border-gray-200 dark:border-gray-800">
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                            <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-indigo-600 text-white">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            </span>
                            <span class="text-base font-bold text-gray-900 dark:text-white">{{ \App\Services\SettingService::siteName() }}</span>
                        </a>
                        <button @click="sidebarOpen = false" class="lg:hidden text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <nav class="flex-1 px-3 py-3 space-y-1 overflow-y-auto">
                        @foreach ($nav as $item)
                            <a href="{{ route($item['route'], $item['params'] ?? []) }}"
                               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition
                                      {{ $item['active'] ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100' }}">
                                <x-nav-icon :name="$item['icon']" class="w-5 h-5 shrink-0" />
                                <span class="truncate">{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </nav>

                    <div class="p-4 border-t border-gray-200 dark:border-gray-800 shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-indigo-100 dark:bg-indigo-500/20 flex items-center justify-center text-indigo-600 dark:text-indigo-300 font-semibold text-sm shrink-0">
                                {{ strtoupper(mb_substr($user?->name ?? '?', 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $user?->name }}</p>
                                <p class="text-xs text-gray-400">{{ $user ? (\App\Models\User::ROLES[$user->role] ?? $user->role) : '' }}</p>
                            </div>
                            <a href="{{ route('profile.edit') }}" class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" title="个人资料">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.9 17.9 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 移动端遮罩 --}}
            <div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false" class="fixed inset-0 z-30 bg-gray-900/50 lg:hidden"></div>

            {{-- 主区域 --}}
            <div class="lg:pl-64">
                <header class="sticky top-0 z-20 h-16 bg-white/80 dark:bg-gray-900/80 backdrop-blur border-b border-gray-200 dark:border-gray-800">
                    <div class="flex items-center gap-3 h-full px-4 sm:px-6">
                        <button @click="sidebarOpen = true" class="lg:hidden text-gray-500 hover:text-gray-700 dark:hover:text-gray-200">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                        </button>

                        <h1 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white truncate hidden sm:block">
                            @yield('page_title', \App\Services\SettingService::siteName())
                        </h1>

                        {{-- 全局搜索（带下拉建议） --}}
                        <div class="ml-auto sm:ml-4 flex-1 sm:flex-initial sm:w-64 lg:w-80" x-data="globalSearch()">
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 text-gray-400 pointer-events-none">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                                </span>
                                <input type="search" x-model="q" @input.debounce.250ms="suggest()"
                                       @keydown.enter="go()" @keydown.escape="items = []; open = false"
                                       @focus="if (items.length) open = true" @click.outside="open = false"
                                       placeholder="搜索工单 / 客户 / 产品…"
                                       class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 pl-8 pr-3 py-1.5 text-sm text-gray-700 dark:text-gray-200 placeholder-gray-400 focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">

                                {{-- 建议下拉 --}}
                                <div x-show="open && items.length > 0" x-cloak x-transition
                                     style="max-height: 340px; overflow-y: auto;"
                                     class="absolute left-0 right-0 top-full mt-1.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-xl z-50">
                                    <template x-for="(it, i) in items" :key="i">
                                        <a :href="it.url"
                                           class="flex items-center gap-2.5 px-3 py-2 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 transition">
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-md shrink-0 text-[10px] font-semibold"
                                                  :class="{
                                                      'bg-indigo-50 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-300': it.type === 'ticket',
                                                      'bg-sky-50 dark:bg-sky-500/20 text-sky-600 dark:text-sky-300': it.type === 'customer',
                                                      'bg-amber-50 dark:bg-amber-500/20 text-amber-600 dark:text-amber-300': it.type === 'product',
                                                  }">
                                                <span x-text="it.type === 'ticket' ? '单' : (it.type === 'customer' ? '客' : '品')"></span>
                                            </span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block text-sm text-gray-800 dark:text-gray-200 truncate" x-text="it.label"></span>
                                                <span class="block text-xs text-gray-400 truncate" x-text="it.meta"></span>
                                            </span>
                                        </a>
                                    </template>
                                    <a :href="'/search?q=' + encodeURIComponent(q)"
                                       class="block px-3 py-2 text-center text-xs font-medium text-indigo-600 dark:text-indigo-400 border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/60">
                                        查看全部结果 →
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 relative shrink-0"
                             x-data="notificationBell()"
                             @ticket:event.window="onEvent($event.detail)">
                            {{-- 站内通知铃铛（外层是 relative flex，下拉挂在同一容器内 absolute 不被 shrink 压缩）--}}
                            <button @click="toggle()"
                                    class="relative shrink-0 p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
                                    title="通知">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
                                <span x-show="unread > 0" x-cloak
                                      class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-[10px] font-semibold text-white"
                                      x-text="unread > 99 ? '99+' : unread"></span>
                            </button>

                            {{-- 通知下拉：与铃铛同属外层 flex 容器，absolute 不再被压缩到 40px --}}
                            <div :class="open ? 'fixed shadow-xl' : 'hidden'"
                                 :style="open
                                     ? 'top:60px; right:16px; width:352px; min-width:352px; max-width:calc(100vw - 1rem); box-sizing:border-box; z-index:60;'
                                     : ''"
                                 class="z-50 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
                                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-800">
                                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">通知</span>
                                    <button @click="markAllRead()" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">全部已读</button>
                                </div>
                                <div class="max-h-96 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800">
                                    <template x-if="items.length === 0">
                                        <div class="py-10 text-center text-sm text-gray-400">暂无通知</div>
                                    </template>
                                    <template x-for="n in items" :key="n.id">
                                        <a href="/notifications"
                                           :href="n.link || '/notifications'" target="_self" data-bell-item
                                           class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800/60 cursor-pointer">
                                            <div class="flex items-start gap-2.5">
                                                <span class="mt-1.5 w-2 h-2 rounded-full shrink-0" :class="n.is_read ? 'bg-gray-200 dark:bg-gray-700' : 'bg-indigo-500'"></span>
                                                <div class="min-w-0 flex-1">
                                                    <p class="text-sm text-gray-800 dark:text-gray-200 break-words whitespace-normal" x-text="n.title"></p>
                                                    <p class="text-xs text-gray-400 mt-1 break-words whitespace-normal line-clamp-2" x-text="n.body || ''"></p>
                                                    <p class="text-[10px] text-gray-300 dark:text-gray-600 mt-1" x-text="formatTime(n.created_at)"></p>
                                                </div>
                                            </div>
                                        </a>
                                    </template>
                                </div>
                                <a href="/notifications"
                                   class="block text-center py-2.5 text-xs font-medium text-indigo-600 dark:text-indigo-400 border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/60">
                                    查看全部通知
                                </a>
                            </div>
                        </div>

                            {{-- 暗色切换 --}}
                            <button x-data="{ dark: document.documentElement.classList.contains('dark') }"
                                    @click="
                                        dark = !dark;
                                        document.documentElement.classList.toggle('dark', dark);
                                        localStorage.setItem('theme', dark ? 'dark' : 'light');
                                    "
                                    class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800" title="切换暗色模式">
                                <template x-if="dark">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" /></svg>
                                </template>
                                <template x-if="!dark">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" /></svg>
                                </template>
                            </button>

                            @auth
                                <a href="{{ route('tickets.create') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500 shadow-sm">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                    <span class="hidden sm:inline">新建工单</span>
                                </a>
                                <form method="POST" action="{{ route('logout') }}" class="inline">
                                    @csrf
                                    <button type="submit" class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800" title="退出登录">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" /></svg>
                                    </button>
                                </form>
                            @endauth
                        </div>
                </header>

                {{-- Flash 消息 --}}
                @if (session('success'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition
                         class="mx-4 sm:mx-6 mt-4 flex items-center justify-between rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-500/30 dark:bg-green-500/10 dark:text-green-300">
                        <span>{{ session('success') }}</span>
                        <button @click="show = false" class="text-green-500 hover:text-green-700">✕</button>
                    </div>
                @endif
                @if (session('error'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition
                         class="mx-4 sm:mx-6 mt-4 flex items-center justify-between rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300">
                        <span>{{ session('error') }}</span>
                        <button @click="show = false" class="text-red-500 hover:text-red-700">✕</button>
                    </div>
                @endif

                {{-- 页面内容（全宽，不做 max-w 居中） --}}
                <main class="p-4 sm:p-6">
                    @yield('content')
                </main>
            </div>
        </div>

        {{-- 移动端浮动「新建工单」（仅客户，小屏常驻，提升转化） --}}
        @auth
            @if (! auth()->user()->isAgent() && ! request()->routeIs('tickets.create'))
                <a href="{{ route('tickets.create') }}"
                   class="md:hidden fixed bottom-5 right-5 z-40 inline-flex items-center justify-center w-14 h-14 rounded-full bg-indigo-600 text-white shadow-xl shadow-indigo-600/30 hover:bg-indigo-500 active:scale-95 transition">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                </a>
            @endif
        @endauth

        {{-- 服务端动态配置注入（路由/WS 参数，供 resources/js 模块读取） --}}
        @php
            $__appConfig = [
                'routes' => [
                    'searchSuggest' => route('search.suggest', ['q' => '__Q__']),
                    'notificationsUnread' => route('notifications.unread-count'),
                    'notificationsLatest' => route('notifications.latest'),
                    'notificationsReadAll' => route('notifications.read-all'),
                    'notificationsRead' => route('notifications.read', ['notification' => '__ID__']),
                    'notificationsIndex' => route('notifications.index'),
                ],
                'ws' => auth()->check() ? [
                    'wsUrl' => \App\Services\WebSocketService::frontendWsUrl(),
                    'uid' => (int) auth()->id(),
                    'token' => \App\Services\WebSocketService::signature((int) auth()->id(), ['ticket.all']),
                    'rooms' => ['ticket.all'],
                ] : null,
            ];
        @endphp
        <script>
            window.__app = @json($__appConfig);
        </script>
    </body>
</html>
