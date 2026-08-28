@props(['label', 'value', 'icon' => 'ticket', 'color' => 'indigo'])

@php
    $colors = [
        'indigo' => 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400',
        'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400',
        'green' => 'bg-green-50 text-green-600 dark:bg-green-500/10 dark:text-green-400',
        'red' => 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400',
        'sky' => 'bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-400',
        'orange' => 'bg-orange-50 text-orange-600 dark:bg-orange-500/10 dark:text-orange-400',
    ];
@endphp

<div class="flex items-center gap-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-5 py-4">
    <span class="inline-flex items-center justify-center w-11 h-11 rounded-xl {{ $colors[$color] ?? $colors['indigo'] }}">
        <x-nav-icon :name="$icon" class="w-5 h-5" />
    </span>
    <div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $value }}</p>
        <p class="text-xs text-gray-400 mt-0.5">{{ $label }}</p>
    </div>
</div>
