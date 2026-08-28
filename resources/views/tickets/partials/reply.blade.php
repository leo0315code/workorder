@php
    $isAgentSide = $reply->user && in_array($reply->user->role, ['agent', 'admin']);
    $isNote = $reply->isNote();
@endphp

<div class="flex gap-3">
    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold shrink-0
                {{ $isAgentSide ? 'bg-indigo-100 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-300' : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
        {{ strtoupper(mb_substr($reply->user?->name ?? '?', 0, 1)) }}
    </div>
    <div class="flex-1 min-w-0">
        <div class="flex flex-wrap items-baseline gap-2">
            <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $reply->user?->name ?? '用户' }}</span>
            @if ($isAgentSide)
                <span class="text-xs text-indigo-500">客服</span>
            @endif
            @if ($isNote)
                <span class="text-xs font-medium text-amber-600 dark:text-amber-400">内部备注</span>
            @endif
            <span class="text-xs text-gray-400">{{ $reply->created_at?->format('Y-m-d H:i') }}</span>
        </div>
        <div class="mt-1 rounded-lg px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap
                    {{ $isNote ? 'bg-amber-50 dark:bg-amber-500/10 text-amber-900 dark:text-amber-200 border border-amber-200 dark:border-amber-500/30' : 'bg-gray-50 dark:bg-gray-800/70' }}">
            {{ $reply->content }}
        </div>
    </div>
</div>
