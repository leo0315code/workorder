@extends('layouts.app')

@section('page_title', '知识库 · '.$article->title)

@section('content')
    {{-- 页头：面包屑 + 元信息 --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <nav class="flex items-center gap-1.5 text-sm text-gray-400 dark:text-gray-500">
            <a href="{{ route('admin.kb.index') }}" class="hover:text-indigo-500 dark:hover:text-indigo-400 transition">知识库</a>
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            <span class="text-gray-600 dark:text-gray-300">{{ $article->category?->name ?? '未分类' }}</span>
        </nav>
        <div class="flex items-center gap-3 text-xs text-gray-400">
            <span>{{ $article->author?->name ?? '—' }}</span>
            <span>·</span>
            <span>{{ $article->updated_at?->format('Y-m-d H:i') }}</span>
            <span>·</span>
            <span>{{ $article->views }} 次阅读</span>
        </div>
    </div>

    {{-- 文章卡 --}}
    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm px-6 sm:px-10 py-8 max-w-4xl">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">{{ $article->title }}</h1>
        <div class="mt-2 flex items-center gap-2 text-xs text-gray-400">
            <span class="rounded-md bg-indigo-50 dark:bg-indigo-500/10 px-2 py-0.5 font-medium text-indigo-600 dark:text-indigo-300 ring-1 ring-inset ring-indigo-200 dark:ring-indigo-500/30">{{ $article->category?->name ?? '未分类' }}</span>
        </div>

        <hr class="my-6 border-gray-100 dark:border-gray-800">

        {{-- Markdown 渲染 --}}
        <article class="prose prose-sm sm:prose-base max-w-none prose-headings:font-bold prose-headings:text-gray-900 dark:prose-headings:text-white
                         prose-p:text-gray-700 dark:prose-p:text-gray-300 prose-a:text-indigo-600 dark:prose-a:text-indigo-400
                         prose-code:text-indigo-600 dark:prose-code:text-indigo-300 prose-pre:bg-gray-900 dark:prose-pre:bg-gray-950 prose-pre:text-gray-100
                         prose-li:text-gray-700 dark:prose-li:text-gray-300 prose-blockquote:border-indigo-200 dark:prose-blockquote:border-indigo-500/40 prose-blockquote:text-gray-600 dark:prose-blockquote:text-gray-400
                         prose-strong:text-gray-900 dark:prose-strong:text-white prose-table:text-gray-700 dark:prose-table:text-gray-300 prose-thead:text-gray-900 dark:prose-thead:text-white">
            {!! \App\Services\MarkdownService::render($article->content) !!}
        </article>
    </div>
@endsection
