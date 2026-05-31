<?php

namespace App\Console\Commands;

use App\Models\MailEvent;
use App\Models\MailServer;
use App\Services\DockerCommandService;
use App\Services\MailLogParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CollectMailEvents extends Command
{
    protected $signature = 'mailadmin:collect';
    protected $description = '增量采集 docker-mailserver 日志';

    public function handle(DockerCommandService $docker, MailLogParser $parser): int
    {
        $server = MailServer::where('is_active', true)->first();
        if (! $server) {
            $this->warn('未配置启用的邮件服务器。');
            return self::SUCCESS;
        }
        $last = DB::table('settings')->where('key', 'collector.last_seen_timestamp')->value('value');
        $since = $last ?: '24h';
        $logs = $docker->logs((int) config('mailadmin.log_tail_limit'), $since);
        $inserted = 0;
        foreach (explode("\n", $logs) as $line) {
            $event = $parser->parseLine($line);
            if (! $event) {
                continue;
            }
            $event['server_id'] = $server->id;
            $event['created_at'] = now();
            $event['raw_hash'] = hash('sha256', $event['raw_line']);
            try {
                MailEvent::create($event);
                $inserted++;
            } catch (\Throwable) {
                // unique key avoids duplicated log ingestion
            }
        }
        DB::table('settings')->updateOrInsert(['key' => 'collector.last_seen_timestamp'], [
            'value' => now()->subMinutes(3)->toIso8601String(),
            'type' => 'datetime',
            'description' => '日志采集器最近读取时间',
            'updated_at' => now(),
        ]);
        $this->info("已采集 {$inserted} 条新事件。");
        return self::SUCCESS;
    }
}
