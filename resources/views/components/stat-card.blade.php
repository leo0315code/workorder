@props(['label', 'value', 'icon' => 'ticket', 'color' => 'indigo'])
{{-- 仪表盘统计卡（图标+数值+标签） --}}

@php
    $colors = [
        'indigo' => 'from-indigo-500 to-violet-500 shadow-indigo-500/25',
        'amber' => 'from-amber-500 to-orange-500 shadow-amber-500/25',
        'green' => 'from-emerald-500 to-green-500 shadow-emerald-500/25',
        'red' => 'from-rose-500 to-red-500 shadow-rose-500/25',
        'sky' => 'from-sky-500 to-cyan-500 shadow-sky-500/25',
        'orange' => 'from-orange-500 to-amber-500 shadow-orange-500/25',
    ];
@endphp

<div class="group flex items-center gap-4 rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-5 py-5 shadow-sm hover:shadow-lg hover:-translate-y-0.5 hover:border-gray-300 dark:hover:border-gray-700 transition">
    <span class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br text-white shadow-lg {{ $colors[$color] ?? $colors['indigo'] }} group-hover:scale-110 group-hover:rotate-3 transition-transform">
        <x-nav-icon :name="$icon" class="w-5.5 h-5.5" />
    </span>
    <div class="min-w-0">
        <p class="text-3xl font-bold text-gray-900 dark:text-white leading-none">{{ $value }}</p>
        <p class="text-xs text-gray-400 mt-1.5 truncate">{{ $label }}</p>
    </div>
</div>
