@extends('layouts.app')

@section('page_title', '通知中心')

@section('content')
    <div class="max-w-3xl">
        <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-1 rounded-lg bg-gray-100 dark:bg-gray-800 p-1">
                <a href="{{ route('notifications.index') }}"
                   class="rounded-md px-3 py-1.5 text-sm font-medium transition {{ ! request()->has('unread') ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-300 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}">
                    全部 <span class="text-xs opacity-70">({{ $notifications->total() }})</span>
                </a>
                <a href="{{ route('notifications.index', ['unread' => 1]) }}"
                   class="rounded-md px-3 py-1.5 text-sm font-medium transition {{ request()->boolean('unread') ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-300 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}">
                    未读 <span class="text-xs opacity-70">({{ $unreadCount }})</span>
                </a>
            </div>
            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                        全部标为已读
                    </button>
                </form>
            @endif
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden shadow-sm divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($notifications as $n)
                <div class="group flex items-start gap-4 px-5 py-4 hover:bg-indigo-50/30 dark:hover:bg-indigo-500/5 transition {{ $n->is_read ? '' : 'bg-indigo-50/40 dark:bg-indigo-500/5' }}">
                    <span class="mt-1.5 w-2.5 h-2.5 rounded-full shrink-0 {{ $n->is_read ? 'bg-gray-200 dark:bg-gray-700' : 'bg-indigo-500 shadow-sm shadow-indigo-500/50' }}"></span>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-baseline gap-2 flex-wrap">
                            <span class="text-sm font-medium {{ $n->is_read ? 'text-gray-600 dark:text-gray-400' : 'text-gray-900 dark:text-gray-100' }}">{{ $n->title }}</span>
                            @if (! $n->is_read)
                                <span class="inline-flex rounded-md bg-indigo-50 dark:bg-indigo-500/10 px-1.5 py-0.5 text-[10px] font-medium text-indigo-600 dark:text-indigo-300 ring-1 ring-inset ring-indigo-200 dark:ring-indigo-500/30">新</span>
                            @endif
                            <span class="text-xs text-gray-400">{{ $n->created_at?->format('Y-m-d H:i') }}</span>
                        </div>
                        @if ($n->body)
                            <p class="mt-0.5 text-sm {{ $n->is_read ? 'text-gray-400' : 'text-gray-500 dark:text-gray-400' }}">{{ $n->body }}</p>
                        @endif
                    </div>
                    @if ($n->link)
                        <form method="POST" action="{{ route('notifications.read', $n) }}">
                            @csrf
                            <button type="submit" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline shrink-0 font-medium">查看</button>
                        </form>
                    @endif
                </div>
            @empty
                <div class="py-16 text-center text-gray-400">
                    @if (request()->boolean('unread'))
                        <p>🎉 没有未读通知</p>
                    @else
                        <p>暂无通知</p>
                    @endif
                </div>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $notifications->links() }}
        </div>
    </div>
@endsection
