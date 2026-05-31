<?php

namespace App\Http\Controllers;

use App\Models\HealthCheck;
use App\Models\MailDailyStat;
use App\Models\QueueItem;
use Illuminate\View\View;

class AntispamController extends Controller
{
    public function __invoke(): View
    {
        $seven = MailDailyStat::where('date', '>=', today()->subDays(6))->get();
        $sent = $seven->sum('sent_count');
        $bounced = $seven->sum('bounced_count');
        $deferred = $seven->sum('deferred_count');
        $attempted = max(1, $sent + $bounced + $deferred);
        $queueCount = QueueItem::count();
        $risks = [];
        if ($queueCount > config('mailadmin.queue_alert_threshold')) {
            $risks[] = '当前队列积压较高，请先检查受阻目标和退信原因。';
        }
        if (($bounced * 100 / $attempted) > config('mailadmin.bounce_rate_alert_threshold')) {
            $risks[] = '退信率超过阈值，建议清理无效收件人并应用退订名单。';
        }
        if (($deferred * 100 / $attempted) > config('mailadmin.deferred_rate_alert_threshold')) {
            $risks[] = '延迟率超过阈值，可能存在对方限速、IP 信誉或 DNS 问题。';
        }
        if (! $risks) {
            $risks[] = '最近统计未发现明显高风险信号。';
        }

        return view('pages.antispam', [
            'health' => HealthCheck::latest('checked_at')->take(20)->get(),
            'risks' => $risks,
            'sent' => $sent,
            'bounced' => $bounced,
            'deferred' => $deferred,
            'queueCount' => $queueCount,
        ]);
    }
}
