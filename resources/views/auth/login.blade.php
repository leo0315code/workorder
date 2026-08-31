<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    {{-- 卡片内标题区 --}}
    <div class="mb-6 text-center">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 dark:bg-indigo-500/10 px-3 py-1 text-xs font-medium text-indigo-600 dark:text-indigo-300 ring-1 ring-inset ring-indigo-200 dark:ring-indigo-500/30">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" /></svg>
            工单服务平台
        </span>
        <h2 class="mt-3 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
            登录账号
        </h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">提交工单、跟进处理进度，随时掌握售后状态</p>
    </div>

    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 px-4 py-3 text-sm text-red-700 dark:text-red-300">{{ session('error') }}</div>
    @endif

    <div x-data="loginTabs()">
        {{-- Tab 切换 --}}
        <div class="grid grid-cols-3 gap-1 mb-6 rounded-xl bg-gray-100 dark:bg-gray-800 p-1">
            <button type="button" @click="tab = 'password'"
                    :class="tab === 'password' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-300 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="rounded-lg px-3 py-2 text-sm font-medium transition">密码登录</button>
            <button type="button" @click="tab = 'phone'"
                    :class="tab === 'phone' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-300 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="rounded-lg px-3 py-2 text-sm font-medium transition">手机号登录</button>
            <button type="button" @click="tab = 'wechat'"
                    :class="tab === 'wechat' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-300 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="rounded-lg px-3 py-2 text-sm font-medium transition">微信扫码</button>
        </div>

        {{-- 密码登录 --}}
        <form method="POST" action="{{ route('login') }}" x-show="tab === 'password'" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">邮箱</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-3.5 flex items-center text-gray-400 dark:text-gray-500">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
                    </span>
                    <input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username"
                           placeholder="you@example.com"
                           class="block w-full h-11 pl-11 pr-4 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">密码</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-3.5 flex items-center text-gray-400 dark:text-gray-500">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                    </span>
                    <input id="password" type="password" name="password" required autocomplete="current-password"
                           placeholder="请输入密码"
                           class="block w-full h-11 pl-11 pr-4 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="flex items-center justify-between">
                <label for="remember_me" class="inline-flex items-center cursor-pointer">
                    <input id="remember_me" type="checkbox"
                           class="rounded-md border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                    <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ __('记住我') }}</span>
                </label>
                @if (Route::has('password.request'))
                    <a class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300 hover:underline" href="{{ route('password.request') }}">{{ __('忘记密码？') }}</a>
                @endif
            </div>

            <button type="submit"
                    class="w-full h-11 inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 text-sm font-semibold text-white shadow-lg shadow-indigo-500/25 hover:from-indigo-500 hover:to-violet-500 hover:shadow-indigo-500/35 active:scale-[0.98] transition">
                登录
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
            </button>
        </form>

        {{-- 手机号登录 --}}
        <form method="POST" action="{{ route('login.phone') }}" x-show="tab === 'phone'" class="space-y-4" @submit="loginPhone($event)">
            @csrf
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">手机号</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-3.5 flex items-center text-gray-400 dark:text-gray-500">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" /></svg>
                    </span>
                    <input id="phone" type="tel" name="phone" x-model="phone" placeholder="11 位手机号" required autocomplete="tel"
                           class="block w-full h-11 pl-11 pr-4 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>
            </div>
            <div>
                <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">验证码</label>
                <div class="flex gap-2">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-3.5 flex items-center text-gray-400 dark:text-gray-500">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        </span>
                        <input id="code" type="text" name="code" x-model="code" placeholder="6 位验证码" maxlength="6" required inputmode="numeric"
                               class="block w-full h-11 pl-11 pr-4 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    </div>
                    <button type="button" @click="sendCode()" :disabled="sending || countdown > 0"
                            class="shrink-0 h-11 rounded-xl border border-indigo-200 dark:border-indigo-500/30 bg-indigo-50 dark:bg-indigo-500/10 px-4 text-sm font-medium text-indigo-600 dark:text-indigo-300 disabled:opacity-50 hover:bg-indigo-100 dark:hover:bg-indigo-500/20 transition"
                            x-text="countdown > 0 ? countdown + 's 后重发' : (sending ? '发送中…' : '获取验证码')"></button>
                </div>
                <template x-if="debugCode">
                    <p class="mt-2 text-xs text-amber-600 dark:text-amber-400">演示模式验证码：<span class="font-mono font-bold" x-text="debugCode"></span>（或使用万能码 123456）</p>
                </template>
                <template x-if="phoneError">
                    <p class="mt-2 text-xs text-red-500" x-text="phoneError"></p>
                </template>
            </div>
            <button type="submit"
                    class="w-full h-11 inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 text-sm font-semibold text-white shadow-lg shadow-indigo-500/25 hover:from-indigo-500 hover:to-violet-500 hover:shadow-indigo-500/35 active:scale-[0.98] transition">
                验证码登录
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
            </button>
            <p class="text-xs text-gray-400 text-center">未注册的手机号将自动创建账号</p>
        </form>

        {{-- 微信扫码 --}}
        <div x-show="tab === 'wechat'" class="flex flex-col items-center py-2">
            <div class="w-52 h-52 rounded-2xl border-2 border-dashed border-gray-300 dark:border-gray-600 flex flex-col items-center justify-center gap-3 overflow-hidden">
                <template x-if="wechatEnabled">
                    <img :src="qrUrl" alt="微信扫码" class="w-full h-full rounded-2xl" x-show="qrUrl">
                </template>
                <template x-if="!wechatEnabled">
                    <div class="text-center px-4">
                        <svg class="w-12 h-12 mx-auto text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" /></svg>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">微信扫码登录</p>
                        <p class="text-xs text-gray-400 mt-1">未配置 AppID，使用演示模式</p>
                    </div>
                </template>
            </div>

            <template x-if="!wechatEnabled">
                <button type="button" @click="mockScan()" :disabled="polling"
                        class="mt-4 rounded-xl bg-green-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-green-500 disabled:opacity-50 shadow-lg shadow-green-500/25 transition">
                    <span x-text="polling ? '正在登录…' : '模拟微信扫码'"></span>
                </button>
            </template>

            <template x-if="wechatError">
                <p class="mt-3 text-sm text-red-500" x-text="wechatError"></p>
            </template>
            <p class="mt-4 text-xs text-gray-400">首次扫码需绑定已有账号或注册新账号</p>
        </div>
    </div>

    <div class="mt-6 pt-5 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-sm">
        <a href="{{ route('register') }}" class="inline-flex items-center gap-1 text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300 hover:underline">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            还没有账号？立即注册
        </a>
        <a href="{{ route('admin.login') }}" class="inline-flex items-center gap-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:underline">
            客服/管理员入口
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
        </a>
    </div>

    <script>
        function loginTabs() {
            return {
                tab: 'password',
                phone: '',
                code: '',
                sending: false,
                countdown: 0,
                debugCode: '',
                phoneError: '',
                // 微信
                wechatEnabled: @json(\App\Services\WechatService::enabled()),
                qrUrl: '',
                scene: '',
                polling: false,
                wechatError: '',

                init() {
                    // 真实/演示模式都先创建扫码会话（演示模式 qr_url 为 null）
                    this.initQr();
                },

                // ---- 手机号 ----
                sendCode() {
                    this.phoneError = '';
                    if (!/^1[3-9]\d{9}$/.test(this.phone)) {
                        this.phoneError = '请输入正确的 11 位手机号';
                        return;
                    }
                    this.sending = true;
                    fetch('{{ route('login.phone.send-code') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: JSON.stringify({ phone: this.phone }),
                    })
                        .then((r) => r.json().then((d) => ({ ok: r.ok, d })))
                        .then(({ ok, d }) => {
                            if (!ok) { this.phoneError = d.message || '发送失败'; return; }
                            this.debugCode = d.debug_code || '';
                            this.countdown = 60;
                            const t = setInterval(() => {
                                this.countdown--;
                                if (this.countdown <= 0) clearInterval(t);
                            }, 1000);
                        })
                        .catch(() => { this.phoneError = '网络异常，请重试'; })
                        .finally(() => { this.sending = false; });
                },

                loginPhone(e) {
                    if (!/^1[3-9]\d{9}$/.test(this.phone)) {
                        e.preventDefault();
                        this.phoneError = '请输入正确的 11 位手机号';
                    }
                },

                // ---- 微信 ----
                initQr() {
                    fetch('{{ route('login.wechat.qr') }}')
                        .then((r) => r.json())
                        .then((d) => {
                            this.scene = d.scene;
                            this.qrUrl = d.qr_url;
                            this.startPoll();
                        });
                },

                mockScan() {
                    if (!this.scene || this.polling) return;
                    this.polling = true;
                    fetch('{{ url('login/wechat/qr') }}/' + this.scene + '/mock', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    })
                        .then((r) => r.json())
                        .then(() => this.startPoll())
                        .catch(() => { this.polling = false; this.wechatError = '模拟扫码失败，请重试'; });
                },

                startPoll() {
                    if (this.polling) return;
                    this.polling = true;
                    const timer = setInterval(() => {
                        fetch('{{ url('login/wechat/qr') }}/' + this.scene + '/status')
                            .then((r) => r.json())
                            .then((d) => {
                                if (d.status === 'success') {
                                    clearInterval(timer);
                                    window.location.href = d.redirect;
                                } else if (d.status === 'need_bind') {
                                    clearInterval(timer);
                                    window.location.href = '{{ url('login/wechat/bind') }}/' + d.scene;
                                } else if (d.status === 'expired') {
                                    clearInterval(timer);
                                    this.polling = false;
                                    this.wechatError = '二维码已过期，请重新获取';
                                }
                            })
                            .catch(() => {});
                    }, 1500);
                    // 5 分钟超时兜底
                    setTimeout(() => clearInterval(timer), 300000);
                },
            };
        }
    </script>
</x-guest-layout>
