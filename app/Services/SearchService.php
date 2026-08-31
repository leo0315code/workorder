<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 全局搜索：跨工单 / 客户档案 / 产品
 * 权限：客服与管理员可搜全部；客户仅能搜自己的工单
 */
class SearchService
{
    public const TYPES = ['tickets', 'customers', 'products'];

    /**
     * 搜索工单
     */
    public static function tickets(User $user, string $q): LengthAwarePaginator
    {
        $query = Ticket::with(['user', 'assignee', 'category', 'product'])
            ->where(function ($w) use ($q) {
                $w->where('no', 'like', "%{$q}%")
                    ->orWhere('subject', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });

        // 客户只能搜自己的工单
        if ($user->isCustomer()) {
            $query->where('user_id', $user->id);
        }

        return $query->orderByDesc('updated_at')
            ->paginate(10, ['*'], 'tp')
            ->withQueryString();
    }

    /**
     * 搜索客户档案（仅客服/管理员）
     */
    public static function customers(User $user, string $q): LengthAwarePaginator
    {
        if ($user->isCustomer()) {
            return Customer::whereRaw('1 = 0')->paginate(10, ['*'], 'cp')->withQueryString();
        }

        return Customer::with('product')
            ->where(function ($w) use ($q) {
                $w->where('company', 'like', "%{$q}%")
                    ->orWhere('contact_name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            })
            ->orderBy('company')->paginate(10, ['*'], 'cp')->withQueryString();
    }

    /**
     * 搜索产品（仅客服/管理员）
     */
    public static function products(User $user, string $q): LengthAwarePaginator
    {
        if ($user->isCustomer()) {
            return Product::whereRaw('1 = 0')->paginate(10, ['*'], 'pp')->withQueryString();
        }

        return Product::where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            })
            ->orderBy('name')->paginate(10, ['*'], 'pp')->withQueryString();
    }

    /**
     * 一次性汇总三类结果
     */
    public static function search(User $user, string $q): array
    {
        return [
            'tickets' => self::tickets($user, $q),
            'customers' => self::customers($user, $q),
            'products' => self::products($user, $q),
        ];
    }

    /**
     * 绝对 URL 转应用内相对路径（兼容 http / https 任意域名）
     */
    protected static function rel(string $url): string
    {
        return NotificationService::normalizeLink($url) ?: $url;
    }

    /**
     * 下拉建议（轻量，最多 8 条）
     */
    public static function suggest(User $user, string $q): array
    {
        $out = [];

        $tickets = Ticket::where(function ($w) use ($q) {
                $w->where('no', 'like', "%{$q}%")
                    ->orWhere('subject', 'like', "%{$q}%");
            })
            ->when($user->isCustomer(), fn ($q2) => $q2->where('user_id', $user->id))
            ->orderByDesc('updated_at')->limit(5)
            ->get(['id', 'no', 'subject', 'status']);

        foreach ($tickets as $t) {
            $out[] = [
                'type' => 'ticket',
                'label' => $t->subject,
                'meta' => $t->no,
                'url' => self::rel(route('tickets.show', $t)),
            ];
        }

        if (! $user->isCustomer()) {
            $customers = Customer::where(function ($w) use ($q) {
                    $w->where('company', 'like', "%{$q}%")
                        ->orWhere('contact_name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%");
                })
                ->limit(4)->get(['id', 'company', 'contact_name', 'phone']);

            foreach ($customers as $c) {
                $out[] = [
                    'type' => 'customer',
                    'label' => $c->company,
                    'meta' => $c->contact_name ?: $c->phone,
                    'url' => self::rel(route('admin.customers.index', ['q' => $c->company])),
                ];
            }

            $products = Product::where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                        ->orWhere('sku', 'like', "%{$q}%");
                })
                ->limit(3)->get(['id', 'name', 'sku']);

            foreach ($products as $p) {
                $out[] = [
                    'type' => 'product',
                    'label' => $p->name,
                    'meta' => $p->sku,
                    'url' => self::rel(route('admin.products.index')),
                ];
            }
        }

        return $out;
    }
}
