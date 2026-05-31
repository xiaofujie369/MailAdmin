<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mail_events') && Schema::hasColumn('mail_events', 'event_time')) {
            DB::statement('ALTER TABLE mail_events MODIFY event_time TIMESTAMP NULL');
        }
    }

    public function down(): void
    {
        //
    }
};
