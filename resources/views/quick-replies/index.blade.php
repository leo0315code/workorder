@extends('layouts.app')

@section('page_title', '快捷回复模板')

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- 新建 --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 h-fit">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">新建快捷回复</h3>
            <form method="POST" action="{{ route('admin.quick-replies.store') }}" class="space-y-4">
                @csrf
                <div>
                    <input type="text" name="title" required placeholder="标题（如：致歉并说明排查中）"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                </div>
                <div>
                    <textarea name="content" required rows="4" placeholder="回复内容模板…"
                              class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm"></textarea>
                </div>
                <button type="submit" class="w-full rounded-lg bg-indigo-600 py-2 text-sm font-medium text-white hover:bg-indigo-500">创建</button>
            </form>
        </div>

        {{-- 列表 --}}
        <div class="lg:col-span-2 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-gray-400 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/60">
                        <th class="py-3 px-4">标题</th>
                        <th class="py-3 px-4">内容</th>
                        <th class="py-3 px-4">状态</th>
                        <th class="py-3 px-4 text-right">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($quickReplies as $q)
                        <tr class="border-b border-gray-100 dark:border-gray-800/60 hover:bg-gray-50 dark:hover:bg-gray-800/40" x-data="{ edit: false, title: {{ json_encode($q->title) }}, content: {{ json_encode($q->content) }} }">
                            <td class="py-3 px-4">
                                <template x-if="!edit">
                                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ $q->title }}</span>
                                </template>
                                <input x-show="edit" x-model="title" type="text" name="title" form="qr-{{ $q->id }}" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                            </td>
                            <td class="py-3 px-4 max-w-[280px]">
                                <template x-if="!edit">
                                    <span class="text-gray-500 dark:text-gray-400 line-clamp-2">{{ $q->content }}</span>
                                </template>
                                <textarea x-show="edit" x-model="content" name="content" rows="3" form="qr-{{ $q->id }}" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm"></textarea>
                            </td>
                            <td class="py-3 px-4">
                                <form method="POST" action="{{ route('admin.quick-replies.update', $q) }}" class="inline">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="title" value="{{ $q->title }}">
                                    <input type="hidden" name="content" value="{{ $q->content }}">
                                    <input type="hidden" name="is_active" value="{{ $q->is_active ? 0 : 1 }}">
                                    <button type="submit"
                                            class="inline-flex rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $q->is_active ? 'bg-green-50 text-green-700 ring-green-200 dark:bg-green-500/10 dark:text-green-300' : 'bg-gray-100 text-gray-500 ring-gray-200 dark:bg-gray-800 dark:text-gray-400' }}">
                                        {{ $q->is_active ? '启用' : '停用' }}
                                    </button>
                                </form>
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <template x-if="!edit">
                                    <button @click="edit = true" class="text-indigo-600 dark:text-indigo-400 hover:underline mr-3">编辑</button>
                                </template>
                                <template x-if="edit">
                                    <button @click="document.getElementById('qr-form-{{ $q->id }}').submit()" class="text-green-600 hover:underline mr-3">保存</button>
                                </template>
                                <form id="qr-form-{{ $q->id }}" method="POST" action="{{ route('admin.quick-replies.update', $q) }}" class="inline hidden">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="title" :value="title">
                                    <input type="hidden" name="content" :value="content">
                                </form>
                                <form method="POST" action="{{ route('admin.quick-replies.destroy', $q) }}" class="inline" onsubmit="return confirm('确定删除？');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:underline">删除</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-12 text-center text-gray-400">暂无快捷回复模板</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
