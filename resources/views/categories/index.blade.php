@extends('layouts.app')

@section('page_title', '分类管理')

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- 新建分类 --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 h-fit">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">新建分类</h3>
            <form method="POST" action="{{ route('admin.categories.store') }}" class="space-y-4">
                @csrf
                <div>
                    <input type="text" name="name" required placeholder="分类名称"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                </div>
                <div>
                    <input type="text" name="description" placeholder="描述（可选）"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                </div>
                <button type="submit" class="w-full rounded-lg bg-indigo-600 py-2 text-sm font-medium text-white hover:bg-indigo-500">创建</button>
            </form>
        </div>

        {{-- 分类列表 --}}
        <div class="lg:col-span-2 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-gray-400 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/60">
                        <th class="py-3 px-4">名称</th>
                        <th class="py-3 px-4">描述</th>
                        <th class="py-3 px-4">工单数</th>
                        <th class="py-3 px-4">状态</th>
                        <th class="py-3 px-4 text-right">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $c)
                        <tr class="border-b border-gray-100 dark:border-gray-800/60 hover:bg-gray-50 dark:hover:bg-gray-800/40">
                            <td class="py-3 px-4 font-medium text-gray-800 dark:text-gray-200">{{ $c->name }}</td>
                            <td class="py-3 px-4 text-gray-500 dark:text-gray-400 max-w-[200px] truncate">{{ $c->description ?: '-' }}</td>
                            <td class="py-3 px-4 text-gray-600 dark:text-gray-300">{{ $c->tickets_count }}</td>
                            <td class="py-3 px-4">
                                <form method="POST" action="{{ route('admin.categories.update', $c) }}" class="inline">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="name" value="{{ $c->name }}">
                                    <input type="hidden" name="description" value="{{ $c->description }}">
                                    <input type="hidden" name="is_active" value="{{ $c->is_active ? 0 : 1 }}">
                                    <button type="submit"
                                            class="inline-flex rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $c->is_active ? 'bg-green-50 text-green-700 ring-green-200 dark:bg-green-500/10 dark:text-green-300' : 'bg-gray-100 text-gray-500 ring-gray-200 dark:bg-gray-800 dark:text-gray-400' }}">
                                        {{ $c->is_active ? '启用' : '停用' }}
                                    </button>
                                </form>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <form method="POST" action="{{ route('admin.categories.destroy', $c) }}" class="inline" onsubmit="return confirm('删除分类不会删除关联工单，确定？');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:underline">删除</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-12 text-center text-gray-400">暂无分类</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-800">
            {{ $categories->links() }}
        </div>
    </div>
@endsection
