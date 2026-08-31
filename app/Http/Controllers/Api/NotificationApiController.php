<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 我的通知 API
 *
 * 只返回当前登录用户的通知（user_id 隔离），支持分页与未读筛选。
 */
class NotificationApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = UserNotification::where('user_id', $request->user()->id);

        if ($request->boolean('unread')) {
            $query->where('is_read', false);
        }

        $notifications = $query->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20))
            ->withQueryString();

        return response()->json([
            'items' => $notifications->getCollection()->map(fn (UserNotification $n) => [
                'id' => $n->id,
                'title' => $n->title,
                'body' => $n->body,
                'link' => $n->link,
                'is_read' => (bool) $n->is_read,
                'created_at' => $n->created_at?->toDateTimeString(),
            ]),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    /**
     * 未读数（App 角标轮询用）
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = UserNotification::where('user_id', $request->user()->id)->where('is_read', false)->count();

        return response()->json(['count' => $count]);
    }

    public function markRead(Request $request, UserNotification $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403, '无权操作该通知');

        $notification->update(['is_read' => true]);

        return response()->json(['message' => '已读']);
    }
}
