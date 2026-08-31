<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(protected ReportService $reports)
    {
    }

    /**
     * 导出当前时间范围报表为 CSV（UTF-8 BOM，直开 Excel）
     * 口径与页面完全一致（共用 ReportService）
     */
    public function export(Request $request)
    {
        $days = $this->reports->normalizeDays($request->input('days', 30));
        $start = $this->reports->startOf($days);

        $summary = $this->reports->summary($start);
        $byStatus = $this->reports->byStatus($start);
        $byPriority = $this->reports->byPriority($start);
        $daily = Ticket::where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->groupBy('d')
            ->orderBy('d')
            ->get();
        $agents = $this->reports->agents($start);
        $ratingStats = $this->reports->ratingStats($start);

        $rows = [];
        $rows[] = ['项目', '数值'];
        $rows[] = ['统计范围', "近 {$days} 天"];
        $rows[] = ['新增工单', $summary['total']];
        $rows[] = ['待处理', $summary['open']];
        $rows[] = ['已解决/关闭', $summary['resolved']];
        $rows[] = ['回复数', $summary['replies']];
        $rows[] = ['满意度评价数', $ratingStats['count']];
        $rows[] = ['平均满意度', $ratingStats['avg'] ?? '暂无'];
        $rows[] = [];
        $rows[] = ['状态', '数量'];
        foreach (TicketController::STATUS_NAMES as $k => $label) {
            $rows[] = [$label, $byStatus[$k] ?? 0];
        }
        $rows[] = [];
        $rows[] = ['优先级', '数量'];
        foreach (TicketController::PRIORITY_NAMES as $k => $label) {
            $rows[] = [$label, $byPriority[$k] ?? 0];
        }
        $rows[] = [];
        $rows[] = ['日期', '新增工单'];
        foreach ($daily as $d) {
            $rows[] = [$d->d, $d->c];
        }
        $rows[] = [];
        $rows[] = ['客服', '处理工单', '回复数', '平均首次响应(小时)', '平均解决时长(小时)', 'SLA 超时'];
        foreach ($agents as $a) {
            $rows[] = [
                $a['name'],
                $a['handled'],
                $a['replies'],
                $a['avg_first_response_hours'] ?? '',
                $a['avg_resolve_hours'] ?? '',
                $a['overdue'],
            ];
        }

        $filename = '工单报表-近'.$days.'天-'.now()->format('Ymd-Hi').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM，Excel 直开不乱码
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function __invoke(Request $request): View
    {
        $days = $this->reports->normalizeDays($request->input('days', 30));
        $start = $this->reports->startOf($days);

        [$dates, $dailySeries] = $this->reports->dailySeries($start);

        $agents = $this->reports->agents($start);
        $byStatus = $this->reports->byStatus($start);
        $byPriority = $this->reports->byPriority($start);
        $byCategory = $this->reports->byCategory($start);
        $summary = $this->reports->summary($start);
        $ratingStats = $this->reports->ratingStats($start);

        return view('reports.index', compact('days', 'dates', 'dailySeries', 'agents', 'byStatus', 'byPriority', 'byCategory', 'summary', 'ratingStats'));
    }
}
