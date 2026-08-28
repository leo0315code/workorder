@props(['label', 'value', 'icon' => 'ticket', 'color' => 'indigo'])

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

<div class="group flex items-center gap-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-5 py-4 shadow-sm hover:shadow-md hover:border-gray-300 dark:hover:border-gray-700 transition">
    <span class="inline-flex items-center justify-center w-11 h-11 rounded-xl bg-gradient-to-br text-white shadow-lg {{ $colors[$color] ?? $colors['indigo'] }} group-hover:scale-105 transition-transform">
        <x-nav-icon :name="$icon" class="w-5 h-5" />
    </span>
    <div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $value }}</p>
        <p class="text-xs text-gray-400 mt-0.5">{{ $label }}</p>
    </div>
</div>
