<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Ticket;
use App\Models\TicketLog;
use App\Models\TicketRating;
use App\Models\TicketReply;
use App\Models\TicketTemplate;
use App\Models\User;
use App\Services\AutoAssignService;
use App\Services\NotificationService;
use App\Services\SettingService;
use App\Services\WebSocketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TicketController extends Controller
{
    public const STATUS_NAMES = [
        Ticket::STATUS_OPEN => '待处理',
        Ticket::STATUS_PENDING => '待客户',
        Ticket::STATUS_IN_PROGRESS => '处理中',
        Ticket::STATUS_RESOLVED => '已解决',
        Ticket::STATUS_CLOSED => '已关闭',
    ];

    public const PRIORITY_NAMES = [
        Ticket::PRIORITY_LOW => '低',
        Ticket::PRIORITY_NORMAL => '普通',
        Ticket::PRIORITY_HIGH => '高',
        Ticket::PRIORITY_URGENT => '紧急',
    ];

    public function index(Request $request): View
    {
        $tickets = $this->filterQuery($request)->orderByDesc('updated_at')->paginate(15)->withQueryString();

        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $agents = User::whereIn('role', ['agent', 'admin'])->orderBy('name')->get();
        $statuses = self::STATUS_NAMES;
        $priorities = self::PRIORITY_NAMES;

        // 列表页实时连接配置（订阅 ticket.all 房间）+ 轮询兜底基准时间
        $uid = (int) Auth::id();
        $wsConfig = [
            'wsUrl' => \App\Services\WebSocketService::frontendWsUrl(),
            'uid' => $uid,
            'token' => WebSocketService::signature($uid, ['ticket.all']),
            'rooms' => ['ticket.all'],
            'pollUrl' => route('tickets.changes'),
            'lastUpdated' => $tickets->first()?->updated_at?->toIso8601String() ?? now()->toIso8601String(),
        ];
        $onlineAgentIds = AutoAssignService::onlineUids() ?: [];

        return view('tickets.index', compact('tickets', 'categories', 'products', 'agents', 'statuses', 'priorities', 'wsConfig', 'onlineAgentIds'));
    }

    /**
     * 列表页轮询兜底：返回 since 之后有更新的工单数（WS 掉线时使用）
     */
    public function changes(Request $request)
    {
        $since = $request->input('since');

        if (! $since) {
            return response()->json(['count' => 0]);
        }

        $count = $this->filterQuery($request)
            ->where('updated_at', '>', $since)
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * 按当前筛选条件构建查询（列表 / 导出共用）
     */
    protected function filterQuery(Request $request)
    {
        $user = Auth::user();

        $query = Ticket::query()->with(['user', 'category', 'product', 'assignee']);

        // 客户只能看自己的
        if (! $user->isAgent()) {
            $query->where('user_id', $user->id);
        } else {
            // 客服：可按“指派给我”过滤
            if ($request->boolean('mine')) {
                $query->where('assignee_id', $user->id);
            }
            if ($request->filled('assignee')) {
                $query->where('assignee_id', $request->integer('assignee'));
            }
            if ($request->boolean('unassigned')) {
                $query->whereNull('assignee_id');
            }
            if ($request->boolean('overdue')) {
                $query->whereNotIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])
                    ->where('sla_due_at', '<', now());
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }
        if ($request->filled('category')) {
            $query->where('category_id', $request->integer('category'));
        }
        if ($request->filled('product')) {
            $query->where('product_id', $request->integer('product'));
        }
        if ($request->filled('q')) {
            $q = trim($request->input('q'));
            $query->where(function ($w) use ($q) {
                $w->where('no', 'like', "%{$q}%")
                    ->orWhere('subject', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        return $query;
    }

    public function create(): View
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $priorities = self::PRIORITY_NAMES;
        $user = Auth::user();
        // 客户带出自己的客户档案（若有）
        $customers = $user->isAgent() ? Customer::orderBy('company')->get() : collect();
        $agents = $user->isAgent() ? User::whereIn('role', ['agent', 'admin'])->orderBy('name')->get() : collect();
        $onlineAgentIds = AutoAssignService::onlineUids() ?: [];
        $templates = $user->isAgent() ? TicketTemplate::where('is_active', true)->orderBy('sort')->orderBy('name')->get() : collect();

        return view('tickets.create', compact('categories', 'products', 'priorities', 'customers', 'agents', 'onlineAgentIds', 'templates'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'product_id' => ['nullable', 'exists:products,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240'], // 10MB
        ]);

        $user = Auth::user();
        $isAgent = $user->isAgent();

        // 非工作时间：客户不可自助提交（客服不受限）
        if (! $isAgent && ! SettingService::isWorkTime()) {
            return back()->withInput()->withErrors([
                'subject' => '当前为非工作时间（工作时间：'.SettingService::workHoursText().'），暂不能提交工单，请在工作时间提交。',
            ]);
        }

        // 负责人：客服可手动指定；未指定且开启自动分配时按负载均衡指派
        $assigneeId = $isAgent && $request->filled('assignee_id')
            ? $request->integer('assignee_id')
            : AutoAssignService::pick();

        $slaHours = SettingService::slaHours();

        $ticket = Ticket::create([
            'no' => $this->nextNo(),
            'user_id' => $user->id,
            'customer_id' => $isAgent && $request->filled('customer_id') ? $request->integer('customer_id') : null,
            'category_id' => $request->filled('category_id') ? $request->integer('category_id') : null,
            'product_id' => $request->filled('product_id') ? $request->integer('product_id') : null,
            'subject' => $request->input('subject'),
            'description' => $request->input('description'),
            'priority' => $request->input('priority'),
            'status' => Ticket::STATUS_OPEN,
            'assignee_id' => $assigneeId,
            'sla_due_at' => now()->addHours($slaHours[$request->input('priority')]),
        ]);

        $this->storeAttachments($request, $ticket);

        // 操作日志
        $this->logAction($ticket, 'created', null, null, null,
            $isAgent ? '客服代客户创建' : '客户提交'.($assigneeId ? '（自动分配）' : ''));

        // 实时推送：新工单
        WebSocketService::pushToRoom('ticket.all', [
            'type' => 'new_ticket',
            'ticket' => $this->ticketPayload($ticket),
        ]);

        // 站内通知：指派给指定客服，否则通知全体客服
        if ($ticket->assignee_id) {
            NotificationService::notifyUser($ticket->assignee_id, '新工单已指派给你', $ticket->no.' · '.$ticket->subject, route('tickets.show', $ticket));
            WebSocketService::pushToUid($ticket->assignee_id, [
                'type' => 'new_ticket',
                'ticket' => $this->ticketPayload($ticket),
            ]);
        } else {
            $agentIds = User::whereIn('role', ['agent', 'admin'])->pluck('id')->all();
            NotificationService::notifyUsers($agentIds, '有新工单待处理', $ticket->no.' · '.$ticket->subject, route('tickets.show', $ticket));
        }

        session()->flash('success', '工单 '.$ticket->no.' 已提交');

        return redirect()->route('tickets.show', $ticket);
    }

    public function show(Ticket $ticket): View
    {
        $this->authorizeView($ticket);

        $ticket->load(['user', 'customer', 'category', 'product', 'assignee', 'attachments', 'rating']);

        // 内部备注仅客服可见
        $ticket->load(['replies' => function ($q) {
            if (! Auth::user()->isAgent()) {
                $q->where('type', TicketReply::TYPE_REPLY);
            }
            $q->with('user');
        }]);

        // 操作日志 / 快捷回复模板（客服可见）
        $isAgent = Auth::user()->isAgent();
        if ($isAgent) {
            $ticket->load('logs');
        }
        $quickReplies = $isAgent ? \App\Models\QuickReply::where('is_active', true)->orderBy('title')->get() : collect();

        $agents = $isAgent
            ? User::whereIn('role', ['agent', 'admin'])->orderBy('name')->get()
            : collect();
        $onlineAgentIds = AutoAssignService::onlineUids() ?: [];

        // WebSocket 鉴权参数
        $wsUid = Auth::id();
        $wsRooms = ['ticket.'.$ticket->id, 'ticket.all'];
        $wsToken = WebSocketService::signature((int) $wsUid, $wsRooms);

        // 前端实时组件配置（避免在 Blade 属性中用复杂 @json）
        $roomConfig = [
            'wsUrl' => \App\Services\WebSocketService::frontendWsUrl(),
            'uid' => (int) $wsUid,
            'token' => $wsToken,
            'rooms' => $wsRooms,
            'lastReplyId' => $ticket->replies->last()?->id ?? 0,
            'pollUrl' => route('tickets.replies', $ticket),
            'isAgent' => $isAgent,
        ];

        return view('tickets.show', compact('ticket', 'agents', 'roomConfig', 'quickReplies', 'onlineAgentIds'));
    }

    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorizeView($ticket);

        $request->validate([
            'content' => ['required', 'string', 'max:10000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240'], // 10MB
        ]);

        $reply = TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'content' => $request->input('content'),
            'type' => TicketReply::TYPE_REPLY,
        ]);

        // 回复附件（挂到工单附件区统一展示）
        $this->storeAttachments($request, $ticket);

        $reopened = in_array($ticket->status, [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED]);

        $ticket->update([
            'last_reply_at' => now(),
            // 客户回复后回到“处理中/待处理”语义：若已解决则重新打开
            'status' => $reopened
                ? Ticket::STATUS_OPEN
                : $ticket->status,
        ]);

        // 操作日志
        $this->logAction($ticket, $reopened ? 'reopened' : 'replied', null, null, null,
            Auth::user()->isAgent() ? '客服回复' : '客户补充说明');

        // 通知对方
        $link = route('tickets.show', $ticket);
        if (Auth::user()->isAgent()) {
            NotificationService::notifyUser($ticket->user_id, '你的工单有新回复', $ticket->no.' · '.$ticket->subject, $link);
        } elseif ($ticket->assignee_id) {
            NotificationService::notifyUser($ticket->assignee_id, '工单有新回复，请处理', $ticket->no.' · '.$ticket->subject, $link);
        } else {
            $agentIds = User::whereIn('role', ['agent', 'admin'])->pluck('id')->all();
            NotificationService::notifyUsers($agentIds, '工单有新回复，请处理', $ticket->no.' · '.$ticket->subject, $link);
        }

        WebSocketService::pushToRoom('ticket.'.$ticket->id, [
            'type' => 'reply',
            'reply' => $this->replyPayload($reply),
            'ticket' => $this->ticketPayload($ticket->fresh(['user', 'category', 'product', 'assignee'])),
        ]);

        session()->flash('success', '回复成功');

        return redirect()->route('tickets.show', $ticket);
    }

    public function note(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorizeStaff($ticket);

        $request->validate([
            'content' => ['required', 'string', 'max:10000'],
        ]);

        TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'content' => $request->input('content'),
            'type' => TicketReply::TYPE_NOTE,
        ]);

        $this->logAction($ticket, 'noted', null, null, null, '添加内部备注');

        session()->flash('success', '内部备注已添加');

        return redirect()->route('tickets.show', $ticket);
    }

    public function update(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorizeStaff($ticket);

        $request->validate([
            'status' => ['nullable', 'in:open,pending,in_progress,resolved,closed'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
            'assignee_id' => ['nullable', 'exists:users,id'],
        ]);

        $data = array_filter($request->only(['status', 'priority', 'assignee_id']), fn ($v) => $v !== null);

        if (isset($data['status'])) {
            if ($data['status'] === Ticket::STATUS_CLOSED) {
                $data['closed_at'] = now();
            } elseif ($ticket->status === Ticket::STATUS_CLOSED) {
                $data['closed_at'] = null;
            }
            if (in_array($data['status'], [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])) {
                $data['closed_at'] = $data['closed_at'] ?? now();
            }
        }

        // 记录变更日志（变更前比较）
        $old = $ticket->only(['status', 'priority', 'assignee_id']);
        $changes = [];
        if (isset($data['status']) && $data['status'] !== $old['status']) {
            $changes[] = ['field' => 'status', 'old' => self::STATUS_NAMES[$old['status']] ?? $old['status'], 'new' => self::STATUS_NAMES[$data['status']] ?? $data['status']];
        }
        if (isset($data['priority']) && $data['priority'] !== $old['priority']) {
            $changes[] = ['field' => 'priority', 'old' => self::PRIORITY_NAMES[$old['priority']] ?? $old['priority'], 'new' => self::PRIORITY_NAMES[$data['priority']] ?? $data['priority']];
        }
        if (array_key_exists('assignee_id', $data) && (int) $data['assignee_id'] !== (int) $old['assignee_id']) {
            $newName = $data['assignee_id'] ? User::find($data['assignee_id'])?->name : null;
            $oldName = $old['assignee_id'] ? User::find($old['assignee_id'])?->name : null;
            $changes[] = ['field' => 'assignee', 'old' => $oldName ?? '未指派', 'new' => $newName ?? '未指派'];
        }

        $ticket->update($data);

        foreach ($changes as $c) {
            $this->logAction($ticket, 'change', $c['field'], $c['old'], $c['new']);
        }

        // 指派变更通知新负责人
        if (isset($data['assignee_id']) && (int) $data['assignee_id'] !== (int) $old['assignee_id'] && $data['assignee_id']) {
            NotificationService::notifyUser((int) $data['assignee_id'], '工单已指派给你', $ticket->no.' · '.$ticket->subject, route('tickets.show', $ticket));
        }
        // 状态变更通知提交人
        if (isset($data['status']) && $data['status'] !== $old['status'] && $ticket->user_id !== Auth::id()) {
            NotificationService::notifyUser($ticket->user_id, '你的工单状态已更新', $ticket->no.' → '.self::STATUS_NAMES[$data['status']], route('tickets.show', $ticket));
        }

        WebSocketService::pushToRoom('ticket.'.$ticket->id, [
            'type' => 'status_changed',
            'ticket' => $this->ticketPayload($ticket->fresh(['user', 'category', 'product', 'assignee'])),
        ]);

        session()->flash('success', '工单已更新');

        return redirect()->route('tickets.show', $ticket);
    }

    /**
     * 满意度评分（仅提交人本人，已解决/关闭后可评一次）
     */
    public function rate(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorizeView($ticket);

        // 仅工单提交人可评分
        if ($ticket->user_id !== Auth::id() || Auth::user()->isAgent()) {
            abort(403);
        }

        if (! in_array($ticket->status, [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])) {
            return back()->with('error', '工单解决或关闭后才能评分');
        }

        $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        TicketRating::updateOrCreate(
            ['ticket_id' => $ticket->id],
            [
                'user_id' => Auth::id(),
                'rating' => $request->integer('rating'),
                'comment' => $request->input('comment'),
            ]
        );

        $this->logAction($ticket, 'change', 'rating', null, $request->input('rating').' 星', '客户满意度评分');

        session()->flash('success', '感谢您的评价！');

        return redirect()->route('tickets.show', $ticket);
    }

    /**
     * 客服认领（抢单）：仅未指派工单，认领后指派给自己
     */
    public function claim(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorizeStaff($ticket);

        if ($ticket->assignee_id !== null) {
            return back()->with('error', '该工单已有负责人，无法认领');
        }

        $ticket->update(['assignee_id' => Auth::id()]);

        $this->logAction($ticket, 'change', 'assignee', '未指派', Auth::user()->name, '客服认领');

        WebSocketService::pushToRoom('ticket.'.$ticket->id, [
            'type' => 'status_changed',
            'ticket' => $this->ticketPayload($ticket->fresh(['user', 'category', 'product', 'assignee'])),
        ]);

        session()->flash('success', '已认领工单 '.$ticket->no.'，请及时处理');

        return redirect()->route('tickets.show', $ticket);
    }

    /**
     * 轮询兜底接口：返回自 $after 之后的新回复（WS 不可用时的降级方案）
     */
    public function pollReplies(Request $request, Ticket $ticket)
    {
        $this->authorizeView($ticket);

        $after = (int) $request->query('after', 0);

        $replies = $ticket->replies()
            ->where('id', '>', $after)
            ->where('type', TicketReply::TYPE_REPLY)
            ->with('user')
            ->get();

        return response()->json([
            'ticket' => $this->ticketPayload($ticket),
            'replies' => $replies->map(fn ($r) => $this->replyPayload($r)),
        ]);
    }

    // ---- helpers ----

    protected function logAction(Ticket $ticket, string $action, ?string $field = null, ?string $old = null, ?string $new = null, ?string $note = null): void
    {
        try {
            TicketLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
                'action' => $action,
                'field' => $field,
                'old_value' => $old,
                'new_value' => $new,
                'note' => $note,
            ]);
        } catch (\Throwable $e) {
            // 日志失败不影响主流程
        }
    }

    /**
     * 导出当前筛选条件下的工单为 CSV（UTF-8 BOM，Excel 可直接打开）
     */
    public function export(Request $request)
    {
        if (! Auth::user()->isAgent()) {
            abort(403);
        }

        $query = $this->filterQuery($request)->with(['user', 'category', 'product', 'assignee']);

        $filename = 'tickets-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            // BOM，保证 Excel 正确识别 UTF-8
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['编号', '主题', '描述', '状态', '优先级', '分类', '产品', '提交人', '电话', '负责人', 'SLA 时限', '创建时间', '更新时间']);

            $query->chunk(200, function ($tickets) use ($out) {
                foreach ($tickets as $t) {
                    fputcsv($out, [
                        $t->no,
                        $t->subject,
                        $t->description,
                        self::STATUS_NAMES[$t->status] ?? $t->status,
                        self::PRIORITY_NAMES[$t->priority] ?? $t->priority,
                        $t->category?->name ?? '',
                        $t->product?->name ?? '',
                        $t->user?->name ?? '',
                        $t->user?->phone ?? '',
                        $t->assignee?->name ?? '',
                        optional($t->sla_due_at)->format('Y-m-d H:i'),
                        optional($t->created_at)->format('Y-m-d H:i'),
                        optional($t->updated_at)->format('Y-m-d H:i'),
                    ]);
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * 批量操作：批量指派 / 批量关闭（客服及以上）
     */
    public function batch(Request $request): RedirectResponse
    {
        $this->authorizeStaff(new Ticket);

        $request->validate([
            'action' => ['required', 'in:assign,close'],
            'ticket_ids' => ['required', 'array', 'min:1'],
            'ticket_ids.*' => ['integer'],
            'assignee_id' => ['nullable', 'exists:users,id'],
        ]);

        $tickets = Ticket::whereIn('id', $request->input('ticket_ids'))->get();
        $action = $request->input('action');

        foreach ($tickets as $ticket) {
            if ($action === 'close') {
                if (! in_array($ticket->status, [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])) {
                    $ticket->update(['status' => Ticket::STATUS_CLOSED, 'closed_at' => now()]);
                    $this->logAction($ticket, 'change', 'status', self::STATUS_NAMES[$ticket->status], '已关闭', '批量关闭');
                }
            } elseif ($action === 'assign' && $request->filled('assignee_id')) {
                $ticket->update(['assignee_id' => $request->integer('assignee_id')]);
                $this->logAction($ticket, 'change', 'assignee', '原负责人', User::find($request->integer('assignee_id'))?->name, '批量指派');
            }
        }

        session()->flash('success', '已对 '.$tickets->count().' 个工单执行「'.($action === 'close' ? '关闭' : '指派').'」');

        return redirect()->back();
    }

    protected function nextNo(): string
    {
        $prefix = 'TK-'.date('Ymd');
        $count = Ticket::where('no', 'like', $prefix.'%')->count() + 1;

        return sprintf('%s-%04d', $prefix, $count);
    }

    protected function storeAttachments(Request $request, Ticket $ticket): void
    {
        if (! $request->hasFile('attachments')) {
            return;
        }

        foreach ($request->file('attachments') as $file) {
            $path = $file->store('tickets/'.$ticket->id, 'public');

            Attachment::create([
                'attachable_type' => Ticket::class,
                'attachable_id' => $ticket->id,
                'user_id' => Auth::id(),
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }
    }

    protected function authorizeView(Ticket $ticket): void
    {
        if (! Auth::user()->isAgent() && $ticket->user_id !== Auth::id()) {
            abort(403);
        }
    }

    protected function authorizeStaff(Ticket $ticket): void
    {
        if (! Auth::user()->isAgent()) {
            abort(403);
        }
    }

    protected function ticketPayload(Ticket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'no' => $ticket->no,
            'subject' => $ticket->subject,
            'status' => $ticket->status,
            'status_label' => self::STATUS_NAMES[$ticket->status] ?? $ticket->status,
            'priority' => $ticket->priority,
            'priority_label' => self::PRIORITY_NAMES[$ticket->priority] ?? $ticket->priority,
            'updated_at' => optional($ticket->updated_at)->format('Y-m-d H:i:s'),
        ];
    }

    protected function replyPayload(TicketReply $reply): array
    {
        return [
            'id' => $reply->id,
            'content' => $reply->content,
            'type' => $reply->type,
            'user' => ['id' => $reply->user?->id, 'name' => $reply->user?->name, 'role' => $reply->user?->role],
            'created_at' => optional($reply->created_at)->format('Y-m-d H:i:s'),
        ];
    }

    public static function downloadAttachment(Attachment $attachment)
    {
        // 越权校验：附件归属的工单须为当前用户可见
        $ticket = $attachment->attachable_type === Ticket::class
            ? Ticket::find($attachment->attachable_id)
            : null;

        abort_unless($ticket, 404);

        $user = Auth::user();
        if (! $user->isAgent() && $ticket->user_id !== $user->id) {
            abort(403);
        }

        abort_if(! Storage::disk('public')->exists($attachment->path), 404);

        return Storage::disk('public')->download($attachment->path, $attachment->original_name);
    }
}
