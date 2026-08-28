<x-guest-layout>
    <div class="mb-6 text-center">
        <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-green-600 text-white mb-3">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" /></svg>
        </span>
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">微信登录 · 首次绑定</h2>
        <p class="text-sm text-gray-500 mt-1">选择绑定已有账号，或注册新账号</p>
    </div>

    <div x-data="{ mode: 'bind' }">
        <div class="grid grid-cols-2 gap-1 mb-6 rounded-lg bg-gray-100 dark:bg-gray-800 p-1">
            <button type="button" @click="mode = 'bind'" :class="mode === 'bind' ? 'bg-white dark:bg-gray-700 text-indigo-600 shadow-sm' : 'text-gray-500'"
                    class="rounded-md px-3 py-2 text-sm font-medium transition">绑定已有账号</button>
            <button type="button" @click="mode = 'register'" :class="mode === 'register' ? 'bg-white dark:bg-gray-700 text-indigo-600 shadow-sm' : 'text-gray-500'"
                    class="rounded-md px-3 py-2 text-sm font-medium transition">注册新账号</button>
        </div>

        {{-- 绑定已有账号 --}}
        <form method="POST" action="{{ route('login.wechat.bind-store', $scene) }}" x-show="mode === 'bind'" class="space-y-4">
            @csrf
            <input type="hidden" name="mode" value="bind">
            <div>
                <x-input-label for="bind_email" :value="__('邮箱')" />
                <x-text-input id="bind_email" class="block mt-1 w-full" type="email" name="email" required autofocus />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="bind_password" :value="__('密码')" />
                <x-text-input id="bind_password" class="block mt-1 w-full" type="password" name="password" required />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
            <x-primary-button class="w-full justify-center">{{ __('绑定并登录') }}</x-primary-button>
        </form>

        {{-- 注册新账号 --}}
        <form method="POST" action="{{ route('login.wechat.bind-store', $scene) }}" x-show="mode === 'register'" class="space-y-4">
            @csrf
            <input type="hidden" name="mode" value="register">
            <div>
                <x-input-label for="reg_name" :value="__('昵称')" />
                <x-text-input id="reg_name" class="block mt-1 w-full" type="text" name="name" required />
            </div>
            <div>
                <x-input-label for="reg_phone" :value="__('手机号（选填，用于找回/售后联系）')" />
                <x-text-input id="reg_phone" class="block mt-1 w-full" type="tel" name="phone" />
                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            </div>
            <x-primary-button class="w-full justify-center">{{ __('注册并登录') }}</x-primary-button>
        </form>
    </div>

    <div class="mt-6 text-center">
        <a href="{{ route('login') }}" class="text-sm text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:underline">← 返回登录</a>
    </div>
</x-guest-layout>
