<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-6 text-center">
        <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-gray-900 text-white mb-3">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" /></svg>
        </span>
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">工单系统 · 管理后台</h2>
        <p class="text-sm text-gray-500 mt-1">仅限客服 / 管理员登录</p>
    </div>

    <form method="POST" action="{{ route('admin.login.store') }}" class="space-y-4">
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
        </div>

        <x-primary-button class="w-full justify-center">{{ __('进入管理后台') }}</x-primary-button>
    </form>

    <div class="mt-6 pt-5 border-t border-gray-100 dark:border-gray-800 text-center">
        <a href="{{ route('login') }}" class="text-sm text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:underline">← 返回用户端登录</a>
    </div>
</x-guest-layout>
