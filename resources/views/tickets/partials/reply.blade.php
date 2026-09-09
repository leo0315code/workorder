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
        <div class="mt-1.5 rounded-xl px-4 py-3 text-sm shadow-sm ring-1 ring-inset
                    {{ $isNote ? 'bg-amber-50 dark:bg-amber-500/10 text-amber-900 dark:text-amber-200 ring-amber-200 dark:ring-amber-500/30' : ($isAgentSide ? 'bg-indigo-50/60 dark:bg-indigo-500/10 text-gray-800 dark:text-gray-200 ring-indigo-100 dark:ring-indigo-500/20' : 'bg-gray-50 dark:bg-gray-800/70 text-gray-700 dark:text-gray-300 ring-gray-100 dark:ring-gray-700/60') }}">
            {{-- pre-wrap 只作用于正文，避免继承到附件区块（模板缩进/换行会被原样渲染撑高气泡） --}}
            <div class="whitespace-pre-wrap leading-relaxed">{{ $reply->content }}</div>

            {{-- 该回复的附件（对话气泡内展示） --}}
            @if ($reply->attachments->isNotEmpty())
                <div class="mt-3 pt-3 border-t border-black/5 dark:border-white/10 space-y-1.5">
                    @foreach ($reply->attachments as $att)
                        <a href="{{ route('attachments.download', $att) }}"
                           class="flex items-center gap-1.5 text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" /></svg>
                            <span class="max-w-[220px] truncate">{{ $att->original_name }}</span>
                            <span class="text-gray-400 shrink-0">({{ number_format($att->size / 1024, 1) }}KB)</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
