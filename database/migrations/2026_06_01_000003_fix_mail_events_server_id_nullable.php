<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mail_events') && Schema::hasColumn('mail_events', 'server_id')) {
            DB::statement('ALTER TABLE mail_events MODIFY server_id BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        //
    }
};
