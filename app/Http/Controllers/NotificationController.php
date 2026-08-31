<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 站内通知（user_notifications 表）
 *
 * 所有查询/写入都按当前登录用户隔离（user_id = auth()->id()）；
 * markRead 对他人通知返回 403。通知由 NotificationService::notifyUser/notifyUsers 产生，
 * 并同步推送到 WebSocket（notificationBell 铃铛实时刷新）。
 */
class NotificationController extends Controller
{
    public function index(): View
    {
        $query = UserNotification::where('user_id', auth()->id());

        if (request()->boolean('unread')) {
            $query->unread();
        }

        $notifications = $query->orderByDesc('created_at')->paginate(20);
        $unreadCount = UserNotification::where('user_id', auth()->id())->unread()->count();

        return view('notifications.index', compact('notifications', 'unreadCount'));
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
            ->map(fn ($n) => [
                'id' => $n->id,
                'title' => $n->title,
                'body' => $n->body,
                'link' => $n->link,
                'is_read' => $n->is_read,
                'created_at' => $n->created_at?->format('Y-m-d H:i'),
            ]);

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
