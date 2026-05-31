<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MailQueueSnapshotCommand extends Command
{
    protected $signature = 'mail:queue-snapshot';
    protected $description = 'Record mail queue snapshot';

    public function handle(): int
    {
        if (!Schema::hasTable('mail_queue_snapshots')) {
            $this->warn('mail_queue_snapshots table missing.');
            return self::SUCCESS;
        }

        $pending = 0;
        $deferred = 0;
        $active = 0;
        $failed = 0;
        $raw = [];

        $output = @shell_exec('postqueue -p 2>/dev/null');
        if ($output) {
            $raw['postqueue'] = mb_substr($output, 0, 5000);
            $lines = preg_split('/\r?\n/', trim($output));
            foreach ($lines as $line) {
                if (preg_match('/^[A-F0-9]/i', $line)) {
                    $pending++;
                }
                if (stripos($line, 'deferred') !== false) {
                    $deferred++;
                }
            }
        }

        DB::table('mail_queue_snapshots')->insert([
            'queue_name' => 'postfix',
            'pending' => $pending,
            'deferred' => $deferred,
            'active' => $active,
            'failed' => $failed,
            'raw' => json_encode($raw, JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->info("Queue snapshot saved: pending={$pending}, deferred={$deferred}");
        return self::SUCCESS;
    }
}
