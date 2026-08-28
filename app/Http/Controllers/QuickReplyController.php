<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\QuickReply;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuickReplyController extends Controller
{
    public function index(): View
    {
        $quickReplies = QuickReply::orderByDesc('updated_at')->get();

        return view('quick-replies.index', compact('quickReplies'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'content' => ['required', 'string', 'max:5000'],
        ]);

        QuickReply::create($data + ['is_active' => true]);

        return redirect()->route('admin.quick-replies.index')->with('success', '快捷回复已创建');
    }

    public function update(Request $request, QuickReply $quickReply): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $quickReply->update($data + ['is_active' => $request->boolean('is_active', $quickReply->is_active)]);

        return redirect()->route('admin.quick-replies.index')->with('success', '快捷回复已更新');
    }

    public function destroy(QuickReply $quickReply): RedirectResponse
    {
        $quickReply->delete();

        return redirect()->route('admin.quick-replies.index')->with('success', '快捷回复已删除');
    }
}
