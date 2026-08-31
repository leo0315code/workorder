{-- 工单状态徽标（待处理/待客户/处理中/已解决/已关闭） --}
@props(['status' => 'open'])

@php
    $map = [
        'open' => ['待处理', 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/30'],
        'pending' => ['待客户', 'bg-purple-50 text-purple-700 ring-purple-200 dark:bg-purple-500/10 dark:text-purple-300 dark:ring-purple-500/30'],
        'in_progress' => ['处理中', 'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-500/10 dark:text-sky-300 dark:ring-sky-500/30'],
        'resolved' => ['已解决', 'bg-green-50 text-green-700 ring-green-200 dark:bg-green-500/10 dark:text-green-300 dark:ring-green-500/30'],
        'closed' => ['已关闭', 'bg-gray-100 text-gray-600 ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700'],
    ];
    [$label, $cls] = $map[$status] ?? [$status, 'bg-gray-100 text-gray-600 ring-gray-200'];
@endphp

<span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $cls }}">{{ $label }}</span>
