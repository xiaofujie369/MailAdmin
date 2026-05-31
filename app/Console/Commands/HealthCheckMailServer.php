<?php

namespace App\Console\Commands;

use App\Models\HealthCheck;
use App\Models\MailDailyStat;
use App\Models\MailServer;
use App\Models\QueueItem;
use App\Services\DockerCommandService;
use Illuminate\Console\Command;

class HealthCheckMailServer extends Command
{
    protected $signature = 'mailadmin:health-check';
    protected $description = '检查 Rspamd、Postfix 和队列健康度';

    public function handle(DockerCommandService $docker): int
    {
        $server = MailServer::where('is_active', true)->first();
        if (! $server) {
            return self::SUCCESS;
        }

        $this->save($server->id, 'postfix', 'ok', 90, 'Postfix 配置读取完成', $docker->postconf());
        $rspamd = $docker->rspamdConfigtest();
        $this->save($server->id, 'rspamd', str_contains(strtolower($rspamd), 'syntax ok') ? 'ok' : 'warning', 80, 'Rspamd configtest 完成', $rspamd);

        $queueCount = QueueItem::count();
        $this->save($server->id, 'queue', $queueCount > config('mailadmin.queue_alert_threshold') ? 'warning' : 'ok', $queueCount > 0 ? 70 : 100, "当前队列 {$queueCount} 封", '');

        $seven = MailDailyStat::where('date', '>=', today()->subDays(6))->get();
        $sent = $seven->sum('sent_count');
        $bounced = $seven->sum('bounced_count');
        $deferred = $seven->sum('deferred_count');
        $attempted = max(1, $sent + $bounced + $deferred);
        $this->save($server->id, 'bounce_rate', ($bounced * 100 / $attempted) > config('mailadmin.bounce_rate_alert_threshold') ? 'warning' : 'ok', 80, '7 日退信率 '.round($bounced * 100 / $attempted, 2).'%', '');
        $this->save($server->id, 'deferred_rate', ($deferred * 100 / $attempted) > config('mailadmin.deferred_rate_alert_threshold') ? 'warning' : 'ok', 80, '7 日延迟率 '.round($deferred * 100 / $attempted, 2).'%', '');

        $this->info('健康检查已完成。');
        return self::SUCCESS;
    }

    private function save(int $serverId, string $type, string $status, int $score, string $message, string $raw): void
    {
        HealthCheck::create([
            'server_id' => $serverId,
            'check_type' => $type,
            'status' => $status,
            'score' => $score,
            'message' => $message,
            'raw_output' => $raw,
            'checked_at' => now(),
            'created_at' => now(),
        ]);
    }
}
