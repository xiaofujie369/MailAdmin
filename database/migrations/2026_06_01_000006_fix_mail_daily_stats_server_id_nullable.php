<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mail_daily_stats') && Schema::hasColumn('mail_daily_stats', 'server_id')) {
            DB::statement('ALTER TABLE mail_daily_stats MODIFY server_id BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        //
    }
};
