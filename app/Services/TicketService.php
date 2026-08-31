<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Controllers\TicketController;
use App\Models\Attachment;
use App\Models\Ticket;
use App\Models\TicketFieldDef;
use App\Models\TicketFieldValue;
use App\Models\TicketLog;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * 工单业务服务：列表查询、创建、回复/备注、更新、标签、附件、日志、@提及、自定义字段、推送载荷
 *
 * 从 TicketController 抽出（原 856 行 → 控制器仅保留路由入口，业务收敛于此）。
 * 所有方法无状态（依赖 Auth facade 取当前用户），可被 Web 控制器与 API 控制器复用。
 */
class TicketService
{
    /**
     * 按当前筛选条件构建查询（列表 / 轮询 / 导出共用）
     */
    public function filterQuery(Request $request): Builder
    {
        $user = Auth::user();

        $query = Ticket::query()->with(['user', 'category', 'product', 'assignee']);

        // 客户只能看自己的
        if (! $user->isAgent()) {
            $query->where('user_id', $user->id);
        } else {
            // 客服：可按"指派给我"过滤
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

        // 按标签筛选（客服工具；客户仅能筛到自己工单上的标签）
        if ($request->filled('tag')) {
            $query->whereHas('tags', fn ($q) => $q->where('tags.id', $request->integer('tag')));
        }

        return $query;
    }

    /**
     * 工单编号：TK-YYYYMMDD-0001（按当天数量递增，并发下可能重复，量级可接受）
     */
    public function nextNo(): string
    {
        $prefix = 'TK-'.date('Ymd');
        $count = Ticket::where('no', 'like', $prefix.'%')->count() + 1;

        return sprintf('%s-%04d', $prefix, $count);
    }

    /**
     * 写操作日志（失败不影响主流程）
     */
    public function logAction(Ticket $ticket, string $action, ?string $field = null, ?string $old = null, ?string $new = null, ?string $note = null): void
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
     * 保存附件到私有盘（纵深防御：mimes 之外按原始扩展名二次校验）
     */
    public function storeAttachments(Request $request, Ticket $ticket): void
    {
        if (! $request->hasFile('attachments')) {
            return;
        }

        foreach ($request->file('attachments') as $file) {
            // 纵深防御：mimes 规则之外再按原始扩展名校验一次，拦掉形如 x.png.php 的双扩展名
            $ext = strtolower((string) $file->getClientOriginalExtension());
            if (! in_array($ext, TicketController::ATTACHMENT_TYPES, true)) {
                continue;
            }

            // 存到私有盘，禁止通过 /storage 直连下载（下载统一走 downloadAttachment 鉴权）
            $path = $file->store('tickets/'.$ticket->id, TicketController::ATTACHMENT_DISK);

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

    /**
     * 查看权限：客户只能看自己的工单
     */
    public function authorizeView(Ticket $ticket): void
    {
        if (! Auth::user()->isAgent() && $ticket->user_id !== Auth::id()) {
            abort(403);
        }
    }

    /**
     * 操作权限：工单管理操作仅客服及以上
     */
    public function authorizeStaff(Ticket $ticket): void
    {
        if (! Auth::user()->isAgent()) {
            abort(403);
        }
    }

    /**
     * 自定义字段必填校验：返回 [field_key => message] 错误数组（空=通过）
     */
    public function validateFieldValues(Request $request): array
    {
        $errors = [];
        $defs = TicketFieldDef::where('is_active', true)->where('is_required', true)->get();

        foreach ($defs as $def) {
            $value = trim((string) $request->input('field_'.$def->key));
            if ($value === '') {
                $errors['field_'.$def->key] = $def->label.'为必填项';
            }
        }

        return $errors;
    }

    /**
     * 保存自定义字段值（ticket_id + field_def_id 唯一，upsert）
     */
    public function storeFieldValues(Request $request, Ticket $ticket): void
    {
        $defs = TicketFieldDef::where('is_active', true)->get();

        foreach ($defs as $def) {
            $value = trim((string) $request->input('field_'.$def->key));
            if ($value === '') {
                continue;
            }
            TicketFieldValue::updateOrCreate(
                ['ticket_id' => $ticket->id, 'field_def_id' => $def->id],
                ['value' => $value]
            );
        }
    }

    /**
     * @提及：解析内容中的 @客服姓名，逐一通知被提及者（排除自己），并 WS 推送
     * 匹配中文名（2-8 字）或英文名/昵称（2-20 字符）
     */
    public function notifyMentions(string $content, Ticket $ticket): void
    {
        $me = Auth::id();
        preg_match_all('/@([\x{4e00}-\x{9fa5}]{2,8}|[A-Za-z][A-Za-z0-9_]{1,19})/u', $content, $m);

        $names = array_unique(array_map('trim', $m[1] ?? []));
        if (! $names) {
            return;
        }

        $mentioned = User::whereIn('role', ['agent', 'admin'])
            ->whereIn('name', $names)
            ->where('id', '!=', $me)
            ->get();

        foreach ($mentioned as $user) {
            NotificationService::notifyUser(
                $user->id,
                '有人 @ 了你',
                $ticket->no.' · '.$ticket->subject,
                route('tickets.show', $ticket)
            );
            WebSocketService::pushToUid($user->id, [
                'type' => 'mention',
                'ticket_id' => $ticket->id,
                'message' => '有人 @ 了你：'.$ticket->no,
            ]);
        }
    }

    /**
     * WS 实时推送的工单载荷（隐藏内部字段）
     */
    public function ticketPayload(Ticket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'no' => $ticket->no,
            'subject' => $ticket->subject,
            'status' => $ticket->status,
            'status_label' => TicketController::STATUS_NAMES[$ticket->status] ?? $ticket->status,
            'priority' => $ticket->priority,
            'priority_label' => TicketController::PRIORITY_NAMES[$ticket->priority] ?? $ticket->priority,
            'updated_at' => optional($ticket->updated_at)->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * WS 实时推送的回复载荷
     */
    public function replyPayload(TicketReply $reply): array
    {
        return [
            'id' => $reply->id,
            'content' => $reply->content,
            'type' => $reply->type,
            'user' => ['id' => $reply->user?->id, 'name' => $reply->user?->name, 'role' => $reply->user?->role],
            'created_at' => optional($reply->created_at)->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 附件下载（static：路由直接调 TicketController::downloadAttachment → 委托此处）
     */
    public static function download(Attachment $attachment)
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

        abort_if(! Storage::disk(TicketController::ATTACHMENT_DISK)->exists($attachment->path), 404);

        // 始终以附件形式下载，禁止浏览器按内容类型内联执行（防 html/svg 存储型 XSS）
        return Storage::disk(TicketController::ATTACHMENT_DISK)->download(
            $attachment->path,
            $attachment->original_name,
            ['Content-Disposition' => 'attachment; filename="'.str_replace('"', '', $attachment->original_name).'"']
        );
    }
}
