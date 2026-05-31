<?php

namespace App\Console\Commands;

use App\Models\MailServer;
use App\Models\QueueItem;
use App\Models\QueueSnapshot as QueueSnapshotModel;
use App\Services\DockerCommandService;
use App\Services\QueueParser;
use Illuminate\Console\Command;

class QueueSnapshot extends Command
{
    protected $signature = 'mailadmin:queue-snapshot';
    protected $description = '采集 Postfix 当前队列快照';

    public function handle(DockerCommandService $docker, QueueParser $parser): int
    {
        $server = MailServer::where('is_active', true)->first();
        if (! $server) {
            return self::SUCCESS;
        }
        $raw = $docker->postqueue();
        $items = $parser->parse($raw);
        QueueSnapshotModel::create([
            'server_id' => $server->id,
            'captured_at' => now(),
            'queue_count' => count($items),
            'raw_output' => $raw,
            'created_at' => now(),
        ]);
        QueueItem::where('server_id', $server->id)->delete();
        foreach ($items as $item) {
            QueueItem::create($item + ['server_id' => $server->id]);
        }
        $this->info('队列快照已更新：'.count($items));
        return self::SUCCESS;
    }
}
