<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function addTimestampIfMissing(string $table, string $column): void
    {
        if (Schema::hasTable($table) && !Schema::hasColumn($table, $column)) {
            Schema::table($table, function (Blueprint $t) use ($column) {
                $t->timestamp($column)->nullable();
            });
        }
    }

    public function up(): void
    {
        foreach ([
            'mail_events',
            'mail_daily_stats',
            'mail_queue_snapshots',
            'system_health_checks',
        ] as $table) {
            $this->addTimestampIfMissing($table, 'created_at');
            $this->addTimestampIfMissing($table, 'updated_at');
        }
    }

    public function down(): void
    {
        //
    }
};
