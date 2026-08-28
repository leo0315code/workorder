@php
    $isAgentSide = $reply->user && in_array($reply->user->role, ['agent', 'admin']);
    $isNote = $reply->isNote();
    $isAgent = $isAgentSide && ! $isNote;
@endphp

<div class="flex gap-3 group">
    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold shrink-0 shadow-sm
                {{ $isNote ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300' : ($isAgentSide ? 'bg-gradient-to-br from-indigo-500 to-violet-500 text-white' : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300') }}">
        {{ strtoupper(mb_substr($reply->user?->name ?? '?', 0, 1)) }}
    </div>
    <div class="flex-1 min-w-0">
        <div class="flex flex-wrap items-baseline gap-2">
            <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $reply->user?->name ?? '用户' }}</span>
            @if ($isAgentSide)
                <span class="text-[11px] font-medium rounded-md px-1.5 py-0.5 {{ $isNote ? 'bg-amber-50 text-amber-600 ring-1 ring-inset ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/30' : 'bg-indigo-50 text-indigo-600 ring-1 ring-inset ring-indigo-200 dark:bg-indigo-500/10 dark:text-indigo-300 dark:ring-indigo-500/30' }}">
                    {{ $isNote ? '内部备注' : '客服' }}
                </span>
            @endif
            <span class="text-xs text-gray-400">{{ $reply->created_at?->format('Y-m-d H:i') }}</span>
        </div>
        <div class="mt-1.5 rounded-xl px-4 py-3 text-sm whitespace-pre-wrap leading-relaxed shadow-sm ring-1 ring-inset
                    {{ $isNote ? 'bg-amber-50 dark:bg-amber-500/10 text-amber-900 dark:text-amber-200 ring-amber-200 dark:ring-amber-500/30' : ($isAgentSide ? 'bg-indigo-50/60 dark:bg-indigo-500/10 text-gray-800 dark:text-gray-200 ring-indigo-100 dark:ring-indigo-500/20' : 'bg-gray-50 dark:bg-gray-800/70 text-gray-700 dark:text-gray-300 ring-gray-100 dark:ring-gray-700/60') }}">
            {{ $reply->content }}
        </div>
    </div>
</div>
