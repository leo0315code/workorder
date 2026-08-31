<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\TicketTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 工单模板管理（模块权限：templates）
 *
 * 隐式绑定注意：资源路由产生的参数名是 {ticket_template}，
 * 控制器方法参数必须写成 $ticketTemplate（Laravel 会转 snake_case 匹配），
 * 若写成 $template 将绑定失败、拿到空模型，update/destroy 会静默无效。
 */
class TicketTemplateController extends Controller
{
    public function index(): View
    {
        $templates = TicketTemplate::with(['category', 'product'])->orderBy('sort')->orderByDesc('updated_at')->paginate(15);

        return view('ticket-templates.index', compact('templates'));
    }

    /**
     * 创建模板；subject/description 支持占位符（如 {客户姓名}），新建工单时由前端替换
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        // checkbox 未勾选时 boolean() 返回 false，新建默认停用需在表单里显式勾选「启用」
        TicketTemplate::create($data + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('admin.ticket-templates.index')->with('success', '工单模板已创建');
    }

    public function update(Request $request, TicketTemplate $ticketTemplate): RedirectResponse
    {
        $data = $this->validateData($request);

        $ticketTemplate->update($data + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('admin.ticket-templates.index')->with('success', '工单模板已更新');
    }

    public function destroy(TicketTemplate $ticketTemplate): RedirectResponse
    {
        $ticketTemplate->delete();

        return redirect()->route('admin.ticket-templates.index')->with('success', '工单模板已删除');
    }

    /**
     * 模板字段校验（store / update 共用）
     */
    protected function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'product_id' => ['nullable', 'exists:products,id'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'sort' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
