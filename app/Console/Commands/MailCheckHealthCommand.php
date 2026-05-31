<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MailCheckHealthCommand extends Command
{
    protected $signature = 'mail:check-health';
    protected $description = 'Record basic mail admin health checks';

    public function handle(): int
    {
        if (!Schema::hasTable('system_health_checks')) {
            $this->warn('system_health_checks table missing.');
            return self::SUCCESS;
        }

        $checks = [];

        try {
            DB::select('select 1');
            $checks[] = ['check_type' => 'database', 'status' => 'ok', 'score' => 100, 'message' => 'Database connection is OK'];
        } catch (\Throwable $e) {
            $checks[] = ['check_type' => 'database', 'status' => 'error', 'score' => 0, 'message' => $e->getMessage()];
        }

        $checks[] = [
            'check_type' => 'mail_events',
            'status' => Schema::hasTable('mail_events') ? 'ok' : 'missing',
            'score' => Schema::hasTable('mail_events') ? 100 : 0,
            'message' => Schema::hasTable('mail_events') ? 'mail_events table exists' : 'mail_events table missing',
        ];

        $checks[] = [
            'check_type' => 'mail_daily_stats',
            'status' => Schema::hasTable('mail_daily_stats') ? 'ok' : 'missing',
            'score' => Schema::hasTable('mail_daily_stats') ? 100 : 0,
            'message' => Schema::hasTable('mail_daily_stats') ? 'mail_daily_stats table exists' : 'mail_daily_stats table missing',
        ];

        foreach ($checks as $check) {
            DB::table('system_health_checks')->insert([
                'check_type' => $check['check_type'],
                'status' => $check['status'],
                'score' => $check['score'],
                'message' => $check['message'],
                'meta' => json_encode(['php' => PHP_VERSION], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->info('Health checks saved.');
        return self::SUCCESS;
    }
}
