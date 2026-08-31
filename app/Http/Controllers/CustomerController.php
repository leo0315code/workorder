<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * 客户档案（模块权限：customers）
 *
 * 注意：本控制器所有重定向必须使用带 admin 前缀的路由名（admin.customers.*），
 * 路由注册在 name('admin.') 分组内，写成 route('customers.index') 会抛
 * RouteNotFoundException 导致 500（历史上因此修过一次）。
 */
class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $customers = $this->filterQuery($request)
            ->with(['user', 'product'])
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString();

        $expiredCount = Customer::where('after_sales_expired_at', '<', now())->count();
        $expiringCount = Customer::whereBetween('after_sales_expired_at', [now(), now()->addDays(7)])->count();

        return view('customers.index', compact('customers', 'expiredCount', 'expiringCount'));
    }

    public function create(): View
    {
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $users = User::where('role', 'customer')->orderBy('name')->get();

        return view('customers.form', ['customer' => null, 'products' => $products, 'users' => $users]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        Customer::create($data);

        return redirect()->route('admin.customers.index')->with('success', '客户档案已创建');
    }

    public function edit(Customer $customer): View
    {
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $users = User::where('role', 'customer')->orderBy('name')->get();

        return view('customers.form', compact('customer', 'products', 'users'));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $customer->update($this->validated($request));

        return redirect()->route('admin.customers.index')->with('success', '客户档案已更新');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return redirect()->route('admin.customers.index')->with('success', '客户档案已删除');
    }

    /**
     * 客户详情：档案 + 售后状态 + 全部关联工单
     */
    public function show(Customer $customer): View
    {
        $customer->load(['user', 'product']);

        $tickets = Ticket::where('customer_id', $customer->id)
            ->with(['category', 'assignee'])
            ->orderByDesc('updated_at')
            ->paginate(10);

        return view('customers.show', compact('customer', 'tickets'));
    }

    /**
     * 导出客户档案 CSV（当前筛选）
     */
    public function export(Request $request)
    {
        $query = $this->filterQuery($request)->with(['user', 'product']);

        $filename = 'customers-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['公司', '联系人', '电话', '邮箱', '地址', '关联产品', '绑定账号', '登记时间', '售后到期', '备注']);

            $query->chunk(200, function ($customers) use ($out) {
                foreach ($customers as $c) {
                    fputcsv($out, [
                        $c->company,
                        $c->contact_name,
                        $c->phone,
                        $c->email,
                        $c->address,
                        $c->product?->name ?? '',
                        $c->user?->email ?? '',
                        optional($c->registered_at)->format('Y-m-d'),
                        optional($c->after_sales_expired_at)->format('Y-m-d'),
                        $c->remark,
                    ]);
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * CSV 导入：按邮箱或（公司+电话）匹配，命中则更新、否则新建
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $path = $request->file('file')->getRealPath();
        $rows = [];
        if (($handle = fopen($path, 'r')) !== false) {
            $header = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                if (array_filter($row)) {
                    $rows[] = array_combine($header, $row);
                }
            }
            fclose($handle);
        }

        if (empty($rows)) {
            return back()->with('error', 'CSV 中没有数据');
        }

        $products = Product::all()->keyBy(fn ($p) => mb_strtolower((string) $p->name));
        $created = 0;
        $updated = 0;
        $errors = 0;

        DB::transaction(function () use ($rows, $products, &$created, &$updated, &$errors) {
            foreach ($rows as $row) {
                try {
                    $company = trim((string) ($row['公司'] ?? ''));
                    $contact = trim((string) ($row['联系人'] ?? ''));
                    $phone = trim((string) ($row['电话'] ?? ''));
                    $email = trim((string) ($row['邮箱'] ?? ''));

                    if ($company === '' && $contact === '') {
                        $errors++;
                        continue;
                    }

                    // 匹配：优先邮箱，其次（公司+电话）
                    $customer = $email !== ''
                        ? Customer::where('email', $email)->first()
                        : Customer::where('company', $company)->where('phone', $phone)->first();

                    $data = [
                        'company' => $company ?: null,
                        'contact_name' => $contact ?: null,
                        'phone' => $phone ?: null,
                        'email' => $email ?: null,
                        'address' => trim((string) ($row['地址'] ?? '')) ?: null,
                        'remark' => trim((string) ($row['备注'] ?? '')) ?: null,
                        'registered_at' => $this->parseDate($row['登记时间'] ?? ''),
                        'after_sales_expired_at' => $this->parseDate($row['售后到期'] ?? ''),
                    ];

                    // 关联产品（按名称匹配）
                    $productName = trim((string) ($row['关联产品'] ?? ''));
                    if ($productName !== '' && isset($products[mb_strtolower($productName)])) {
                        $data['product_id'] = $products[mb_strtolower($productName)]->id;
                        // 未填售后到期时按保修期自动计算
                        if (! $data['after_sales_expired_at'] && $data['registered_at']) {
                            $data['after_sales_expired_at'] = \Illuminate\Support\Carbon::parse($data['registered_at'])
                                ->addDays((int) $products[mb_strtolower($productName)]->warranty_days);
                        }
                    }

                    // 绑定账号（按邮箱）
                    if ($email !== '') {
                        $data['user_id'] = User::where('email', $email)->value('id');
                    }

                    if ($customer) {
                        $customer->update($data);
                        $updated++;
                    } else {
                        Customer::create($data);
                        $created++;
                    }
                } catch (\Throwable) {
                    $errors++;
                }
            }
        });

        session()->flash('success', "导入完成：新增 {$created}，更新 {$updated}".($errors ? "，失败 {$errors}" : ''));

        return redirect()->route('admin.customers.index');
    }

    protected function parseDate(?string $value): ?\Illuminate\Support\Carbon
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        // 兼容 YYYY-MM-DD、YYYY/M/D、YYYYMMDD、Excel 序列号
        if (is_numeric($value) && (float) $value > 30000) {
            return \Illuminate\Support\Carbon::createFromDate(1899, 12, 30)->addDays((float) $value);
        }

        return \Illuminate\Support\Carbon::parse($value);
    }

    /**
     * 客户列表筛选（列表 / 导出共用）
     */
    protected function filterQuery(Request $request)
    {
        $query = Customer::query();

        if ($request->filled('q')) {
            $q = trim($request->input('q'));
            $query->where(function ($w) use ($q) {
                $w->where('company', 'like', "%{$q}%")
                    ->orWhere('contact_name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }
        if ($request->filled('warranty')) {
            match ($request->input('warranty')) {
                'expired' => $query->where('after_sales_expired_at', '<', now()),
                'expiring' => $query->whereBetween('after_sales_expired_at', [now(), now()->addDays(7)]),
                'active' => $query->where('after_sales_expired_at', '>', now()),
                default => null,
            };
        }

        return $query;
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'company' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'product_id' => ['nullable', 'exists:products,id'],
            'registered_at' => ['nullable', 'date'],
            'after_sales_expired_at' => ['nullable', 'date'],
            'remark' => ['nullable', 'string', 'max:2000'],
        ]);

        // 若只填了登记时间，按所选产品保修期自动计算售后到期
        // 注意：nullable 字段未提交时不在 $data 中，必须用 ?? null 访问，直接取键会 Undefined array key 500
        if (blank($data['after_sales_expired_at'] ?? null)
            && filled($data['registered_at'] ?? null)
            && filled($data['product_id'] ?? null)) {
            $product = Product::find($data['product_id']);
            $data['after_sales_expired_at'] = \Illuminate\Support\Carbon::parse($data['registered_at'])
                ->addDays((int) $product?->warranty_days);
        }

        return $data;
    }
}
