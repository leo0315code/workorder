{-- 优先级徽标（低/普通/高/紧急，配色区分） --}
@props(['priority' => 'normal'])

@php
    $map = [
        'low' => ['低', 'bg-gray-100 text-gray-600 ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700'],
        'normal' => ['普通', 'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-500/10 dark:text-sky-300 dark:ring-sky-500/30'],
        'high' => ['高', 'bg-orange-50 text-orange-700 ring-orange-200 dark:bg-orange-500/10 dark:text-orange-300 dark:ring-orange-500/30'],
        'urgent' => ['紧急', 'bg-red-50 text-red-700 ring-red-200 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/30'],
    ];
    [$label, $cls] = $map[$priority] ?? [$priority, 'bg-gray-100 text-gray-600 ring-gray-200'];
@endphp

<span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $cls }}">{{ $label }}</span>
