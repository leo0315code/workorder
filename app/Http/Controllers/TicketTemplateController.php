<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\TicketTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketTemplateController extends Controller
{
    public function index(): View
    {
        $templates = TicketTemplate::with(['category', 'product'])->orderBy('sort')->orderByDesc('updated_at')->paginate(15);

        return view('ticket-templates.index', compact('templates'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        TicketTemplate::create($data + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('admin.ticket-templates.index')->with('success', '工单模板已创建');
    }

    public function update(Request $request, TicketTemplate $template): RedirectResponse
    {
        $data = $this->validateData($request);

        $template->update($data + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('admin.ticket-templates.index')->with('success', '工单模板已更新');
    }

    public function destroy(TicketTemplate $template): RedirectResponse
    {
        $template->delete();

        return redirect()->route('admin.ticket-templates.index')->with('success', '工单模板已删除');
    }

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
