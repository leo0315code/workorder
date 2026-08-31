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

        {{-- 删除账号 --}}
        <x-panel title="删除账号" icon="trash">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                账号删除后，所有数据将被永久清除。删除前请确认已备份需要保留的信息。
            </p>

            <div class="mt-4" x-data="{ open: {{ $errors->userDeletion->isNotEmpty() ? 'true' : 'false' }} }">
                <button type="button" @click="open = true"
                        class="inline-flex items-center rounded-lg border border-red-200 dark:border-red-500/30 bg-red-50 dark:bg-red-500/10 px-4 py-2 text-sm font-medium text-red-700 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-500/20 transition">
                    删除账号
                </button>

                <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50" @click.self="open = false">
                    <div class="w-full max-w-md rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-6 shadow-xl">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">确认删除账号？</h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            此操作不可撤销。请输入当前密码以确认。
                        </p>

                        <form method="POST" action="{{ route('profile.destroy') }}" class="mt-5 space-y-4">
                            @csrf
                            @method('delete')

                            <div>
                                <label for="delete_password" class="sr-only">密码</label>
                                <input type="password" id="delete_password" name="password" required placeholder="当前密码"
                                       class="block w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:border-red-500 focus:ring-red-500">
                                @error('password', 'userDeletion')
                                    <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex justify-end gap-3">
                                <button type="button" @click="open = false"
                                        class="rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                    取消
                                </button>
                                <button type="submit"
                                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-500 transition">
                                    确认删除
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </x-panel>
    </div>
@endsection
