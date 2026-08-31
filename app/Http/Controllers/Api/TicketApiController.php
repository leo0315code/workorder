<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Services\AutoAssignService;
use App\Services\NotificationService;
use App\Services\SettingService;
use App\Services\WebSocketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * 工单 API（App / 小程序 / 第三方）
 *
 * 权限边界（与 Web 端一致）：
 * - 客户：只能看/回复自己的工单
 * - 客服：可看全部工单、可指派、可回复
 */
class TicketApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Ticket::with(['user:id,name', 'category:id,name', 'assignee:id,name', 'tags']);

        // 客户只看自己的
        if (! $request->user()->isAgent()) {
            $query->where('user_id', $request->user()->id);
        }

        // 状态筛选：open=待处理中，resolved=已解决，all=全部
        $status = $request->input('status', 'open');
        if ($status === 'open') {
            $query->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_PENDING, Ticket::STATUS_IN_PROGRESS]);
        } elseif ($status === 'resolved') {
            $query->whereIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED]);
        }

        // 按标签筛选
        if ($request->filled('tag_id')) {
            $query->whereHas('tags', fn ($q) => $q->where('tags.id', $request->integer('tag_id')));
        }

        $tickets = $query->orderByDesc('updated_at')->paginate(15);

        return response()->json([
            'items' => $tickets->getCollection()->map(fn (Ticket $t) => $this->payload($t)),
            'pagination' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'total' => $tickets->total(),
            ],
        ]);
    }

    public function show(Request $request, Ticket $ticket): JsonResponse
    {
        abort_unless($this->canView($request->user(), $ticket), 403, '无权查看该工单');

        $ticket->load(['user:id,name', 'category:id,name', 'product:id,name,sku', 'assignee:id,name', 'tags']);

        $replies = $ticket->replies()
            ->with('user:id,name,role')
            ->where(function ($q) use ($request) {
                // 客户只看 reply；客服连内部备注一起看
                if (! $request->user()->isAgent()) {
                    $q->where('type', TicketReply::TYPE_REPLY);
                }
            })
            ->orderBy('created_at')
            ->get()
            ->map(fn (TicketReply $r) => [
                'id' => $r->id,
                'type' => $r->type,
                'content' => $r->content,
                'user' => ['id' => $r->user_id, 'name' => $r->user?->name],
                'created_at' => $r->created_at?->toDateTimeString(),
            ]);

        return response()->json([
            'ticket' => $this->payload($ticket),
            'replies' => $replies,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'product_id' => ['nullable', 'exists:products,id'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'assignee_id' => ['nullable', 'exists:users,id'],
        ]);

        $user = $request->user();
        $isAgent = $user->isAgent();

        // 非工作时间：客户不可自助提交（客服不受限）——与 Web 端一致
        if (! $isAgent && ! SettingService::isWorkTime()) {
            return response()->json([
                'message' => '当前为非工作时间（工作时间：'.SettingService::workHoursText().'），暂不能提交工单。',
            ], 422);
        }

        // 负责人：客服可手动指定；否则自动分配
        $assigneeId = $isAgent && $request->filled('assignee_id')
            ? (int) $data['assignee_id']
            : AutoAssignService::pick();

        $slaHours = SettingService::slaHours();

        $ticket = Ticket::create([
            'no' => $this->nextNo(),
            'user_id' => $user->id,
            'category_id' => isset($data['category_id']) ? (int) $data['category_id'] : null,
            'product_id' => isset($data['product_id']) ? (int) $data['product_id'] : null,
            'subject' => $data['subject'],
            'description' => $data['description'],
            'priority' => $data['priority'],
            'status' => Ticket::STATUS_OPEN,
            'assignee_id' => $assigneeId,
            'sla_due_at' => now()->addHours($slaHours[$data['priority']]),
        ]);

        // 实时推送 + 站内通知（与 Web 端相同触发点）
        WebSocketService::pushToRoom('ticket.all', [
            'type' => 'new_ticket',
            'ticket' => ['id' => $ticket->id, 'no' => $ticket->no, 'subject' => $ticket->subject],
        ]);

        if ($ticket->assignee_id) {
            NotificationService::notifyUser($ticket->assignee_id, '新工单已指派给你', $ticket->no.' · '.$ticket->subject, route('tickets.show', $ticket));
        } else {
            NotificationService::notifyUsers(
                \App\Models\User::whereIn('role', ['agent', 'admin'])->pluck('id')->all(),
                '新工单待认领', $ticket->no.' · '.$ticket->subject, route('tickets.show', $ticket)
            );
        }

        // 重复工单识别：近 24h 同主题未关闭 → 附加 duplicate 提示（不阻止）
        $duplicate = (new \App\Services\TicketService())->duplicateOf($ticket->subject, $user->id, $ticket->id);

        return response()->json([
            'message' => '工单已提交',
            'ticket' => $this->payload($ticket),
            'duplicate' => $duplicate
                ? ['ticket_id' => $duplicate->id, 'no' => $duplicate->no, 'subject' => $duplicate->subject]
                : null,
        ], 201);
    }

    public function reply(Request $request, Ticket $ticket): JsonResponse
    {
        abort_unless($this->canView($request->user(), $ticket), 403, '无权操作该工单');

        $data = $request->validate([
            'content' => ['required', 'string', 'max:10000'],
        ]);

        $user = $request->user();

        // 对外回复统一为 reply（note 仅内部，不通过 API 暴露）
        $reply = TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'content' => $data['content'],
            'type' => TicketReply::TYPE_REPLY,
        ]);

        // 客户回复已解决/关闭工单 → 重新打开（与 Web 端行为一致）
        if (! $user->isAgent() && in_array($ticket->status, [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])) {
            $ticket->update(['status' => Ticket::STATUS_OPEN, 'closed_at' => null]);
        }

        // 客服回复 → 通知客户；客户回复 → 通知负责人/全体客服
        if ($user->isAgent()) {
            NotificationService::notifyUser($ticket->user_id, '工单有新回复', $ticket->no.' · '.$ticket->subject, route('tickets.show', $ticket));
        } elseif ($ticket->assignee_id) {
            NotificationService::notifyUser($ticket->assignee_id, '客户有新回复', $ticket->no.' · '.$ticket->subject, route('tickets.show', $ticket));
        } else {
            NotificationService::notifyUsers(
                \App\Models\User::whereIn('role', ['agent', 'admin'])->pluck('id')->all(),
                '工单有新回复', $ticket->no.' · '.$ticket->subject, route('tickets.show', $ticket)
            );
        }

        WebSocketService::pushToRoom('ticket.all', [
            'type' => 'ticket_reply',
            'ticket_id' => $ticket->id,
            'reply' => ['id' => $reply->id, 'content' => $reply->content, 'user_id' => $user->id],
        ]);

        return response()->json([
            'message' => '回复成功',
            'reply' => [
                'id' => $reply->id,
                'content' => $reply->content,
                'created_at' => $reply->created_at?->toDateTimeString(),
            ],
        ], 201);
    }

    /**
     * 权限：客服/管理员可看全部；客户只能看自己的
     */
    protected function canView($user, Ticket $ticket): bool
    {
        return $user->isAgent() || $ticket->user_id === $user->id;
    }

    /**
     * 对外工单结构（隐藏内部字段）
     */
    protected function payload(Ticket $t): array
    {
        return [
            'tags' => $t->relationLoaded('tags')
                ? $t->tags->map(fn ($tag) => ['id' => $tag->id, 'name' => $tag->name, 'color' => $tag->color])->values()
                : [],
            'id' => $t->id,
            'no' => $t->no,
            'subject' => $t->subject,
            'description' => $t->description,
            'status' => $t->status,
            'priority' => $t->priority,
            'category' => $t->category?->name,
            'product' => $t->product?->name,
            'assignee' => $t->assignee?->name,
            'sla_due_at' => $t->sla_due_at?->toDateTimeString(),
            'created_at' => $t->created_at?->toDateTimeString(),
            'updated_at' => $t->updated_at?->toDateTimeString(),
        ];
    }

    /**
     * 工单编号（与 Web 端同源生成规则）
     */
    protected function nextNo(): string
    {
        $last = Ticket::orderByDesc('id')->value('no');
        $seq = $last ? ((int) substr($last, -4) + 1) : 1;

        return 'TK-'.date('ymd').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
