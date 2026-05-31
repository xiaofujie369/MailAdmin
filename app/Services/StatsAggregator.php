<?php

namespace App\Services;

use App\Models\MailDailyStat;
use App\Models\MailEvent;
use App\Models\MailMonthlyStat;
use App\Models\MailServer;
use Illuminate\Support\Carbon;

class StatsAggregator
{
    public function aggregate(?MailServer $server = null): void
    {
        $server ??= MailServer::where('is_active', true)->first();
        if (! $server) {
            return;
        }

        $start = now()->subDays(365)->startOfDay();
        MailEvent::query()
            ->where('server_id', $server->id)
            ->where('event_time', '>=', $start)
            ->selectRaw('DATE(event_time) as d')
            ->groupBy('d')
            ->pluck('d')
            ->each(fn ($date) => $this->aggregateDay($server, Carbon::parse($date)));

        MailDailyStat::query()
            ->where('server_id', $server->id)
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as m")
            ->groupBy('m')
            ->pluck('m')
            ->each(fn ($month) => $this->aggregateMonth($server, $month));
    }

    private function aggregateDay(MailServer $server, Carbon $date): void
    {
        $events = MailEvent::where('server_id', $server->id)->whereDate('event_time', $date)->get();
        $counts = $events->countBy('event_type');
        $sent = (int) ($counts['sent'] ?? 0);
        $bounced = (int) ($counts['bounced'] ?? 0);
        $deferred = (int) ($counts['deferred'] ?? 0);
        $attempted = max(1, $sent + $bounced + $deferred);

        MailDailyStat::updateOrCreate(
            ['server_id' => $server->id, 'date' => $date->toDateString()],
            [
                'sent_count' => $sent,
                'bounced_count' => $bounced,
                'deferred_count' => $deferred,
                'reject_count' => (int) ($counts['reject'] ?? 0),
                'warning_count' => (int) ($counts['warning'] ?? 0),
                'connect_count' => (int) ($counts['connect'] ?? 0),
                'auth_count' => (int) ($counts['auth'] ?? 0),
                'spam_count' => (int) ($counts['spam'] ?? 0),
                'failure_rate' => round(($bounced + $deferred) * 100 / $attempted, 2),
                'bounce_rate' => round($bounced * 100 / $attempted, 2),
                'deferred_rate' => round($deferred * 100 / $attempted, 2),
            ]
        );
    }

    private function aggregateMonth(MailServer $server, string $month): void
    {
        $rows = MailDailyStat::where('server_id', $server->id)->where('date', 'like', $month.'%')->get();
        $sent = $rows->sum('sent_count');
        $bounced = $rows->sum('bounced_count');
        $deferred = $rows->sum('deferred_count');
        $attempted = max(1, $sent + $bounced + $deferred);

        MailMonthlyStat::updateOrCreate(
            ['server_id' => $server->id, 'month' => $month],
            [
                'sent_count' => $sent,
                'bounced_count' => $bounced,
                'deferred_count' => $deferred,
                'reject_count' => $rows->sum('reject_count'),
                'failure_rate' => round(($bounced + $deferred) * 100 / $attempted, 2),
                'bounce_rate' => round($bounced * 100 / $attempted, 2),
                'deferred_rate' => round($deferred * 100 / $attempted, 2),
            ]
        );
    }
}
