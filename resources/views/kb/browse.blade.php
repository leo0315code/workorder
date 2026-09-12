@extends('layouts.app')

@section('page_title', '帮助中心')

@section('content')
    <div class="space-y-5">
        {{-- 页头 + 搜索 --}}
        <div class="rounded-2xl bg-gradient-to-r from-indigo-600 via-indigo-600 to-violet-600 shadow-lg shadow-indigo-500/25 px-4 sm:px-6 py-5 sm:py-6">
            <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold text-white">帮助中心</h1>
                    <p class="mt-1 text-sm text-indigo-100/90">常见问题与使用指南，先搜一搜也许能直接解决</p>
                </div>
                <form method="GET" action="{{ route('kb.browse') }}" class="sm:w-80 shrink-0">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-white/60 pointer-events-none">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                        </span>
                        <input type="search" name="q" value="{{ request('q') }}" placeholder="搜索常见问题…"
                               class="w-full rounded-xl border-0 bg-white/95 pl-9 pr-3 py-2.5 text-sm text-gray-800 placeholder-gray-400 shadow-sm focus:ring-2 focus:ring-white/60 transition">
                    </div>
                </form>
            </div>
        </div>

        {{-- 分类筛选 --}}
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('kb.browse') }}"
               class="inline-flex items-center rounded-full px-3.5 py-1.5 text-sm font-medium transition
                      {{ ! request('category') ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:border-indigo-300' }}">
                全部
            </a>
            @foreach ($categories as $cat)
                <a href="{{ route('kb.browse', ['category' => $cat->id, 'q' => request('q')]) }}"
                   class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-sm font-medium transition
                          {{ (string) request('category') === (string) $cat->id ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:border-indigo-300' }}">
                    {{ $cat->name }}
                    <span class="text-xs opacity-60">{{ $cat->articles_count }}</span>
                </a>
            @endforeach
        </div>

        {{-- 文章列表 --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @forelse ($articles as $article)
                <a href="{{ route('kb.show', $article) }}"
                   class="group rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm p-4 sm:p-5 hover:border-indigo-300 dark:hover:border-indigo-500/40 hover:shadow-md transition">
                    <div class="flex items-center gap-2 mb-2">
                        @if ($article->category)
                            <span class="inline-flex rounded-md bg-indigo-50 dark:bg-indigo-500/10 px-2 py-0.5 text-[11px] font-medium text-indigo-600 dark:text-indigo-300">{{ $article->category->name }}</span>
                        @endif
                        <span class="ml-auto text-xs text-gray-400">{{ $article->updated_at?->format('Y-m-d') }}</span>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 group-hover:text-indigo-600 dark:group-hover:text-indigo-300 transition">
                        {{ $article->title }}
                    </h3>
                    <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400 line-clamp-2">{{ \Illuminate\Support\Str::limit(strip_tags($article->content), 120) }}</p>
                    <div class="mt-3 flex items-center gap-1 text-xs text-gray-400">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                        {{ number_format($article->views) }} 次浏览
                        <span class="ml-auto text-indigo-500 group-hover:translate-x-0.5 transition">阅读 →</span>
                    </div>
                </a>
            @empty
                <div class="md:col-span-2 py-16 text-center text-sm text-gray-400">没有找到相关文章，可提交工单咨询</div>
            @endforelse
        </div>

        @if ($articles->hasPages())
            <div>{{ $articles->links() }}</div>
        @endif
    </div>
@endsection
