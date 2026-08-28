@props(['title' => '', 'class' => ''])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 '.$class]) }}>
    @if ($title)
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">{{ $title }}</h3>
    @endif
    {{ $slot }}
</div>
