<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-6 text-center">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">用户登录</h2>
        <p class="text-sm text-gray-500 mt-1">提交工单、跟进处理进度</p>
    </div>

    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 px-4 py-3 text-sm text-red-700 dark:text-red-300">{{ session('error') }}</div>
    @endif

    <div x-data="loginTabs()">
        {{-- Tab 切换 --}}
        <div class="grid grid-cols-3 gap-1 mb-6 rounded-lg bg-gray-100 dark:bg-gray-800 p-1">
            <button type="button" @click="tab = 'password'" :class="tab === 'password' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-300 shadow-sm' : 'text-gray-500 dark:text-gray-400'"
                    class="rounded-md px-3 py-2 text-sm font-medium transition">密码登录</button>
            <button type="button" @click="tab = 'phone'" :class="tab === 'phone' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-300 shadow-sm' : 'text-gray-500 dark:text-gray-400'"
                    class="rounded-md px-3 py-2 text-sm font-medium transition">手机号登录</button>
            <button type="button" @click="tab = 'wechat'" :class="tab === 'wechat' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-300 shadow-sm' : 'text-gray-500 dark:text-gray-400'"
                    class="rounded-md px-3 py-2 text-sm font-medium transition">微信扫码</button>
        </div>

        {{-- 密码登录 --}}
        <form method="POST" action="{{ route('login') }}" x-show="tab === 'password'" class="space-y-4">
            @csrf
            <div>
                <x-input-label for="email" :value="__('邮箱')" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="password" :value="__('密码')" />
                <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
            <div class="flex items-center justify-between">
                <label for="remember_me" class="inline-flex items-center">
                    <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                    <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ __('记住我') }}</span>
                </label>
                @if (Route::has('password.request'))
                    <a class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 underline" href="{{ route('password.request') }}">{{ __('忘记密码？') }}</a>
                @endif
            </div>
            <x-primary-button class="w-full justify-center">{{ __('登录') }}</x-primary-button>
        </form>

        {{-- 手机号登录 --}}
        <form method="POST" action="{{ route('login.phone') }}" x-show="tab === 'phone'" class="space-y-4" @submit="loginPhone($event)">
            @csrf
            <div>
                <x-input-label for="phone" :value="__('手机号')" />
                <x-text-input id="phone" class="block mt-1 w-full" type="tel" name="phone" x-model="phone" placeholder="11 位手机号" required autocomplete="tel" />
            </div>
            <div>
                <x-input-label for="code" :value="__('验证码')" />
                <div class="flex gap-2">
                    <x-text-input id="code" class="block mt-1 w-full" type="text" name="code" x-model="code" placeholder="6 位验证码" maxlength="6" required inputmode="numeric" />
                    <button type="button" @click="sendCode()" :disabled="sending || countdown > 0"
                            class="mt-1 shrink-0 rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm font-medium text-indigo-600 dark:text-indigo-400 disabled:opacity-50"
                            x-text="countdown > 0 ? countdown + 's 后重发' : (sending ? '发送中…' : '获取验证码')"></button>
                </div>
                <template x-if="debugCode">
                    <p class="mt-2 text-xs text-amber-600 dark:text-amber-400">演示模式验证码：<span class="font-mono font-bold" x-text="debugCode"></span>（或使用万能码 123456）</p>
                </template>
                <template x-if="phoneError">
                    <p class="mt-2 text-xs text-red-500" x-text="phoneError"></p>
                </template>
            </div>
            <x-primary-button class="w-full justify-center">{{ __('验证码登录') }}</x-primary-button>
            <p class="text-xs text-gray-400 text-center">未注册的手机号将自动创建账号</p>
        </form>

        {{-- 微信扫码 --}}
        <div x-show="tab === 'wechat'" class="flex flex-col items-center py-2">
            <div class="w-48 h-48 rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-600 flex flex-col items-center justify-center gap-3">
                <template x-if="wechatEnabled">
                    <img :src="qrUrl" alt="微信扫码" class="w-full h-full rounded-xl" x-show="qrUrl">
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
                        class="mt-4 rounded-lg bg-green-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-green-500 disabled:opacity-50">
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
        <a href="{{ route('register') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">还没有账号？立即注册</a>
        <a href="{{ route('admin.login') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:underline">客服/管理员入口 →</a>
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
