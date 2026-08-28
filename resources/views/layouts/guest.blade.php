<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ \App\Services\SettingService::siteName() }} · {{ config('app.name', '工单系统') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased dark:bg-gray-950">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 relative overflow-hidden
                    bg-gradient-to-br from-indigo-50 via-white to-violet-50
                    dark:from-gray-950 dark:via-gray-900 dark:to-gray-950">

            {{-- 背景装饰 --}}
            <div class="pointer-events-none absolute -top-24 -left-24 w-96 h-96 rounded-full bg-indigo-200/40 dark:bg-indigo-500/10 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-32 -right-24 w-[28rem] h-[28rem] rounded-full bg-violet-200/40 dark:bg-violet-500/10 blur-3xl"></div>
            <div class="pointer-events-none absolute top-1/3 right-1/4 w-40 h-40 rounded-full bg-sky-100/60 dark:bg-sky-500/5 blur-2xl"></div>

            <div class="relative w-full sm:max-w-md px-6">
                {{-- 品牌区 --}}
                <div class="mb-6 flex flex-col items-center">
                    <span class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-600 text-white shadow-lg shadow-indigo-500/25">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                    </span>
                    <h1 class="mt-3 text-xl font-bold text-gray-900 dark:text-white">{{ \App\Services\SettingService::siteName() }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">工单服务 · 高效响应</p>
                </div>

                <div class="px-6 py-6 bg-white/90 dark:bg-gray-900/80 backdrop-blur rounded-2xl shadow-xl shadow-gray-200/60 dark:shadow-black/30 ring-1 ring-gray-200/60 dark:ring-gray-800">
                    {{ $slot }}
                </div>

                <p class="mt-6 text-center text-xs text-gray-400 dark:text-gray-600">© {{ date('Y') }} {{ \App\Services\SettingService::siteName() }} · 有问题，随时找我们</p>
            </div>
        </div>
    </body>
</html>
