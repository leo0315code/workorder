<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        $notifications = UserNotification::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function unreadCount(): JsonResponse
    {
        $count = UserNotification::where('user_id', auth()->id())->unread()->count();

        return response()->json(['count' => $count]);
    }

    public function latest(): JsonResponse
    {
        $items = UserNotification::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->limit(8)
            ->get(['id', 'title', 'body', 'link', 'is_read', 'created_at'])
            ->map(fn ($n) => $n + ['created_at' => $n->created_at?->format('Y-m-d H:i')]);

        return response()->json(['items' => $items]);
    }

    public function markRead(UserNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === auth()->id(), 403);

        $notification->update(['is_read' => true]);

        if ($notification->link) {
            return redirect()->to($notification->link);
        }

        return redirect()->route('notifications.index');
    }

    public function markAllRead(): RedirectResponse
    {
        UserNotification::where('user_id', auth()->id())->unread()->update(['is_read' => true]);

        return back()->with('success', '全部已读');
    }
}
