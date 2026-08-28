@extends('layouts.app')

@section('page_title', '新建工单')

@section('content')
    @php $isAgent = auth()->user()->isAgent(); @endphp

    <div class="max-w-3xl">
        <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data"
              x-data="{ files: [] }">
            @csrf

            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">主题 <span class="text-red-500">*</span></label>
                    <input type="text" name="subject" value="{{ old('subject') }}" required maxlength="255" placeholder="简要描述问题"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    @error('subject')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">分类</label>
                        <select name="category_id" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                            <option value="">请选择</option>
                            @foreach ($categories as $c)
                                <option value="{{ $c->id }}" @selected((string) old('category_id') === (string) $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">关联产品</label>
                        <select name="product_id" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                            <option value="">请选择</option>
                            @foreach ($products as $p)
                                <option value="{{ $p->id }}" @selected((string) old('product_id') === (string) $p->id)>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">优先级 <span class="text-red-500">*</span></label>
                        <select name="priority" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                            @foreach ($priorities as $k => $label)
                                <option value="{{ $k }}" @selected(old('priority', 'normal') === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @if ($isAgent && $customers->isNotEmpty())
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">客户档案</label>
                            <select name="customer_id" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                                <option value="">不关联</option>
                                @foreach ($customers as $cu)
                                    <option value="{{ $cu->id }}" @selected((string) old('customer_id') === (string) $cu->id)>
                                        {{ $cu->company ?: $cu->contact_name ?: '客户#'.$cu->id }}{{ $cu->after_sales_expired_at ? '（售后至 '.$cu->after_sales_expired_at->format('Y-m-d').'）' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">指派给</label>
                            <select name="assignee_id" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                                <option value="">暂不指派</option>
                                @foreach ($agents as $a)
                                    <option value="{{ $a->id }}" @selected((string) old('assignee_id') === (string) $a->id)>{{ $a->name }}（{{ \App\Models\User::ROLES[$a->role] }}）</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">问题描述 <span class="text-red-500">*</span></label>
                    <textarea name="description" required rows="6" maxlength="10000" placeholder="请详细描述遇到的问题、复现步骤、期望结果等"
                              class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('description') }}</textarea>
                    @error('description')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">附件（最多 5 个，每个 ≤10MB）</label>
                    <input type="file" name="attachments[]" multiple
                           @change="files = Array.from($event.target.files).map(f => f.name)"
                           class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-indigo-700 dark:file:bg-indigo-500/10 dark:file:text-indigo-300 hover:file:bg-indigo-100">
                    <div class="mt-2 space-y-1">
                        <template x-for="f in files" :key="f">
                            <p class="text-xs text-gray-500 dark:text-gray-400" x-text="f"></p>
                        </template>
                    </div>
                    @error('attachments')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('tickets.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">取消</a>
                    <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-500 shadow-sm">提交工单</button>
                </div>
            </div>
        </form>
    </div>
@endsection
