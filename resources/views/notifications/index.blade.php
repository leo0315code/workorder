@extends('layouts.app')

@section('page_title', '通知中心')

@section('content')
    <div class="max-w-3xl">
        <div class="mb-4 flex items-center justify-between">
            <p class="text-sm text-gray-500 dark:text-gray-400">共 {{ $notifications->total() }} 条通知</p>
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button type="submit" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">全部标为已读</button>
            </form>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($notifications as $n)
                <div class="flex items-start gap-4 px-5 py-4 {{ $n->is_read ? 'opacity-60' : '' }}">
                    <span class="mt-1.5 w-2 h-2 rounded-full shrink-0 {{ $n->is_read ? 'bg-gray-200 dark:bg-gray-700' : 'bg-indigo-500' }}"></span>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-baseline gap-2 flex-wrap">
                            <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $n->title }}</span>
                            <span class="text-xs text-gray-400">{{ $n->created_at?->format('Y-m-d H:i') }}</span>
                        </div>
                        @if ($n->body)
                            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ $n->body }}</p>
                        @endif
                    </div>
                    @if ($n->link)
                        <form method="POST" action="{{ route('notifications.read', $n) }}">
                            @csrf
                            <button type="submit" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline shrink-0">查看</button>
                        </form>
                    @endif
                </div>
            @empty
                <div class="py-16 text-center text-gray-400">暂无通知</div>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $notifications->links() }}
        </div>
    </div>
@endsection
