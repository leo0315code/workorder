<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TicketFieldDef;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * 工单自定义字段管理（仅管理员）
 *
 * 定义建单表单上的附加字段（文本/数字/下拉/日期），
 * 创建工单时按 is_required 校验；详情页统一展示。
 */
class TicketFieldDefController extends Controller
{
    public function index(): View
    {
        $defs = TicketFieldDef::orderBy('sort')->orderBy('id')->get();

        return view('field-defs.index', compact('defs'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['key'] = $data['key'] ?: Str::slug($data['label'], '_') ?: 'field_'.time();

        TicketFieldDef::create($data + [
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.field-defs.index')->with('success', '字段已创建');
    }

    public function update(Request $request, TicketFieldDef $def): RedirectResponse
    {
        $def->update($this->validateData($request) + [
            'is_active' => $request->boolean('is_active', $def->is_active),
        ]);

        return redirect()->route('admin.field-defs.index')->with('success', '字段已更新');
    }

    public function destroy(TicketFieldDef $def): RedirectResponse
    {
        $def->delete();

        return redirect()->route('admin.field-defs.index')->with('success', '字段已删除');
    }

    protected function validateData(Request $request): array
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:50'],
            'key' => ['nullable', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/'],
            'type' => ['required', 'in:text,number,select,date'],
            'options' => ['nullable', 'string', 'max:1000'],
            'is_required' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        // 显式覆盖：select 选项转数组（不能用 + 合并，左侧优先会保留原始字符串）
        $data['options'] = $this->parseOptions($request->input('options'));

        return $data;
    }

    /**
     * select 选项：逗号分隔字符串 → 数组（去除空项）
     */
    protected function parseOptions(?string $raw): ?array
    {
        if (! $raw || trim($raw) === '') {
            return null;
        }

        $items = array_values(array_filter(array_map('trim', explode(',', $raw)), fn ($v) => $v !== ''));

        return $items ?: null;
    }
}
