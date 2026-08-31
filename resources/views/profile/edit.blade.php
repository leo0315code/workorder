@extends('layouts.app')

@section('page_title', '个人资料')

@section('content')
    <div class="max-w-2xl space-y-5">
        {{-- 个人信息 --}}
        <x-panel title="个人信息" icon="user">
            <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                @csrf
                @method('patch')

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">姓名</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required autocomplete="name"
                           class="mt-1.5 block w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500">
                    @error('name')
                        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">邮箱</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="username"
                           class="mt-1.5 block w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500">
                    @error('email')
                        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror

                    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            你的邮箱地址尚未验证。
                            <button form="send-verification" class="text-indigo-600 dark:text-indigo-400 hover:underline">点击重新发送验证邮件</button>
                        </p>
                    @endif
                </div>

                <div class="flex items-center gap-3 pt-1">
                    <button type="submit" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 transition">
                        保存
                    </button>
                    @if (session('status') === 'profile-updated')
                        <span x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 2000)" x-transition class="text-sm text-green-600 dark:text-green-400">已保存</span>
                    @endif
                </div>
            </form>

            <form id="send-verification" method="POST" action="{{ route('verification.send') }}" class="hidden">
                @csrf
            </form>
        </x-panel>

        {{-- 修改密码 --}}
        <x-panel title="修改密码" icon="lock">
            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf
                @method('put')

                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">当前密码</label>
                    <input type="password" id="current_password" name="current_password" required autocomplete="current-password"
                           class="mt-1.5 block w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500">
                    @error('current_password', 'updatePassword')
                        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">新密码</label>
                    <input type="password" id="password" name="password" required autocomplete="new-password"
                           class="mt-1.5 block w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500">
                    @error('password', 'updatePassword')
                        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">确认新密码</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password"
                           class="mt-1.5 block w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500">
                    @error('password_confirmation', 'updatePassword')
                        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-3 pt-1">
                    <button type="submit" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 transition">
                        保存
                    </button>
                    @if (session('status') === 'password-updated')
                        <span x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 2000)" x-transition class="text-sm text-green-600 dark:text-green-400">已保存</span>
                    @endif
                </div>
            </form>
        </x-panel>
    </div>
@endsection
