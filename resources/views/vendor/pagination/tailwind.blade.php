@if ($paginator->hasPages())
    <nav role="navigation" aria-label="分页导航">

        {{-- 移动端 --}}
        <div class="flex gap-2 items-center justify-between sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-400 bg-gray-50 border border-gray-200 cursor-not-allowed leading-5 rounded-md dark:text-gray-500 dark:bg-gray-800 dark:border-gray-700">上一页</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 leading-5 rounded-md hover:bg-gray-50 dark:text-gray-200 dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-900">上一页</a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 leading-5 rounded-md hover:bg-gray-50 dark:text-gray-200 dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-900">下一页</a>
            @else
                <span class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-400 bg-gray-50 border border-gray-200 cursor-not-allowed leading-5 rounded-md dark:text-gray-500 dark:bg-gray-800 dark:border-gray-700">下一页</span>
            @endif
        </div>

        {{-- 桌面端 --}}
        <div class="hidden sm:flex-1 sm:flex sm:gap-2 sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    显示
                    @if ($paginator->firstItem())
                        <span class="font-medium text-gray-700 dark:text-gray-200">{{ $paginator->firstItem() }}</span>
                        -
                        <span class="font-medium text-gray-700 dark:text-gray-200">{{ $paginator->lastItem() }}</span>
                    @else
                        {{ $paginator->count() }}
                    @endif
                    条，共 <span class="font-medium text-gray-700 dark:text-gray-200">{{ $paginator->total() }}</span> 条
                </p>
            </div>

            <div>
                <span class="inline-flex shadow-sm rounded-md">
                    {{-- 上一页 --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="上一页">
                            <span class="inline-flex items-center px-2.5 py-2 text-sm font-medium text-gray-400 bg-white border border-gray-200 cursor-not-allowed rounded-l-md leading-5 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-500" aria-hidden="true">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                            </span>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center px-2.5 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-200 rounded-l-md leading-5 hover:text-indigo-600 focus:outline-none focus:ring ring-indigo-200 active:bg-gray-100 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-900 dark:hover:text-indigo-300" aria-label="上一页">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                        </a>
                    @endif

                    {{-- 页码 --}}
                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <span aria-disabled="true">
                                <span class="inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-500 bg-white border border-gray-200 cursor-default leading-5 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400">{{ $element }}</span>
                            </span>
                        @endif

                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page">
                                        <span class="inline-flex items-center px-4 py-2 -ml-px text-sm font-semibold text-white bg-indigo-600 border border-indigo-600 cursor-default leading-5">{{ $page }}</span>
                                    </span>
                                @else
                                    <a href="{{ $url }}" class="inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-600 bg-white border border-gray-200 leading-5 hover:text-indigo-600 hover:bg-gray-50 focus:outline-none focus:ring ring-indigo-200 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-900 dark:hover:text-indigo-300" aria-label="第 {{ $page }} 页">{{ $page }}</a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- 下一页 --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center px-2.5 py-2 -ml-px text-sm font-medium text-gray-500 bg-white border border-gray-200 rounded-r-md leading-5 hover:text-indigo-600 focus:outline-none focus:ring ring-indigo-200 active:bg-gray-100 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-900 dark:hover:text-indigo-300" aria-label="下一页">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                        </a>
                    @else
                        <span aria-disabled="true" aria-label="下一页">
                            <span class="inline-flex items-center px-2.5 py-2 -ml-px text-sm font-medium text-gray-400 bg-white border border-gray-200 cursor-not-allowed rounded-r-md leading-5 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-500" aria-hidden="true">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                            </span>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
