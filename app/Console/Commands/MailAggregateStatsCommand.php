<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MailAggregateStatsCommand extends Command
{
    protected $signature = 'mail:aggregate-stats';
    protected $description = 'Aggregate mail_events into mail_daily_stats';

    public function handle(): int
    {
        if (!Schema::hasTable('mail_events') || !Schema::hasTable('mail_daily_stats')) {
            $this->warn('mail_events or mail_daily_stats table missing.');
            return self::SUCCESS;
        }

        $rows = DB::table('mail_events')
            ->selectRaw('DATE(COALESCE(occurred_at, created_at)) as stat_date')
            ->selectRaw("SUM(CASE WHEN event_type = 'sent' THEN 1 ELSE 0 END) as sent_count")
            ->selectRaw("SUM(CASE WHEN event_type = 'bounced' THEN 1 ELSE 0 END) as bounced_count")
            ->selectRaw("SUM(CASE WHEN event_type = 'deferred' THEN 1 ELSE 0 END) as deferred_count")
            ->selectRaw("SUM(CASE WHEN event_type = 'reject' THEN 1 ELSE 0 END) as reject_count")
            ->whereNotNull(DB::raw('COALESCE(occurred_at, created_at)'))
            ->groupBy('stat_date')
            ->orderBy('stat_date')
            ->get();

        foreach ($rows as $row) {
            $sent = (int) $row->sent_count;
            $bounced = (int) $row->bounced_count;
            $deferred = (int) $row->deferred_count;
            $reject = (int) $row->reject_count;
            $failed = $bounced + $deferred + $reject;
            $total = $sent + $failed;
            $failureRate = $total > 0 ? round($failed / $total * 100, 2) : 0;

            DB::table('mail_daily_stats')->updateOrInsert(
                ['date' => $row->stat_date],
                [
                    'sent_count' => $sent,
                    'bounced_count' => $bounced,
                    'deferred_count' => $deferred,
                    'reject_count' => $reject,
                    'failure_rate' => $failureRate,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $this->info('Aggregated '.$rows->count().' daily rows.');
        return self::SUCCESS;
    }
}
