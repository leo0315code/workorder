{{-- 邮箱验证提示页（注册后未验证时的引导） --}}
<x-guest-layout>
    {{-- 卡片内标题区 --}}
    <div class="mb-6 text-center">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-sky-50 dark:bg-sky-500/10 px-3 py-1 text-xs font-medium text-sky-600 dark:text-sky-300 ring-1 ring-inset ring-sky-200 dark:ring-sky-500/30">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 9v.906a2.25 2.25 0 0 1-1.183 1.981l-6.478 3.488M2.25 9v.906a2.25 2.25 0 0 0 1.183 1.981l6.478 3.488m8.839 2.51-4.66-2.51m0 0-1.023-.55a2.25 2.25 0 0 0-2.134 0l-1.022.55m0 0-4.661 2.51m16.5 1.615a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75a2.25 2.25 0 0 1 2.25-2.25h15a2.25 2.25 0 0 1 2.25 2.25v10.5Z" /></svg>
            邮箱验证
        </span>
        <h2 class="mt-3 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">验证您的邮箱</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">点击我们发送到您邮箱的验证链接即可完成验证</p>
    </div>

    <div class="rounded-xl bg-sky-50 dark:bg-sky-500/5 border border-sky-200 dark:border-sky-500/20 px-4 py-3 text-sm text-sky-800 dark:text-sky-200 leading-relaxed">
        感谢注册！请点击我们刚发送到您邮箱的验证链接完成验证。
        如果没有收到邮件，可以点击下方按钮重新发送。
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mt-3 rounded-lg bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/30 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-300">
            新的验证链接已发送到您注册时填写的邮箱地址。
        </div>
    @endif

    <div class="mt-6 flex items-center justify-between gap-3">
        <form method="POST" action="{{ route('verification.send') }}" class="flex-1">
            @csrf
            <button type="submit"
                    class="w-full h-11 inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 text-sm font-semibold text-white shadow-lg shadow-indigo-500/25 hover:from-indigo-500 hover:to-violet-500 hover:shadow-indigo-500/35 active:scale-[0.98] transition">
                重新发送验证邮件
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="h-11 px-4 rounded-xl border border-gray-300 dark:border-gray-700 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                退出登录
            </button>
        </form>
    </div>
</x-guest-layout>
