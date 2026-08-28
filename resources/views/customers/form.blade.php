@extends('layouts.app')

@section('page_title', $customer ? '编辑客户' : '新建客户')

@section('content')
    <div class="max-w-2xl">
        <form method="POST" action="{{ $customer ? route('admin.customers.update', $customer) : route('admin.customers.store') }}"
              x-data="{ reg: '{{ old('registered_at', $customer?->registered_at?->format('Y-m-d')) }}', pid: '{{ old('product_id', $customer?->product_id) }}' }">
            @csrf
            @if ($customer) @method('PUT') @endif

            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">公司名称</label>
                        <input type="text" name="company" value="{{ old('company', $customer?->company) }}"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">联系人</label>
                        <input type="text" name="contact_name" value="{{ old('contact_name', $customer?->contact_name) }}"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">电话</label>
                        <input type="text" name="phone" value="{{ old('phone', $customer?->phone) }}"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">邮箱</label>
                        <input type="email" name="email" value="{{ old('email', $customer?->email) }}"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">地址</label>
                    <input type="text" name="address" value="{{ old('address', $customer?->address) }}"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">绑定注册账号</label>
                        <select name="user_id" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                            <option value="">不绑定</option>
                            @foreach ($users as $u)
                                <option value="{{ $u->id }}" @selected((string) old('user_id', $customer?->user_id) === (string) $u->id)>{{ $u->name }}（{{ $u->email }}）</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-400">用于关联客户注册账号与其工单</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">关联产品</label>
                        <select name="product_id" x-model="pid" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                            <option value="">不关联</option>
                            @foreach ($products as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}（保修 {{ $p->warranty_days }} 天）</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">登记/购买时间</label>
                        <input type="date" name="registered_at" x-model="reg"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                        <p class="mt-1 text-xs text-gray-400">填写后，若未单独填售后到期，将按所选产品保修期自动计算</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">售后到期时间</label>
                        <input type="date" name="after_sales_expired_at" value="{{ old('after_sales_expired_at', $customer?->after_sales_expired_at?->format('Y-m-d')) }}"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">备注</label>
                    <textarea name="remark" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">{{ old('remark', $customer?->remark) }}</textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('admin.customers.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">取消</a>
                    <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-500">保存</button>
                </div>
            </div>
        </form>
    </div>
@endsection
