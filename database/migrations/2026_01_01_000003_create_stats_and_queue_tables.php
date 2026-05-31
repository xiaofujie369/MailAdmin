<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mail_daily_stats', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('server_id')->constrained('mail_servers')->cascadeOnDelete();
            $table->date('date')->index();
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('bounced_count')->default(0);
            $table->unsignedInteger('deferred_count')->default(0);
            $table->unsignedInteger('reject_count')->default(0);
            $table->unsignedInteger('warning_count')->default(0);
            $table->unsignedInteger('connect_count')->default(0);
            $table->unsignedInteger('auth_count')->default(0);
            $table->unsignedInteger('spam_count')->default(0);
            $table->decimal('failure_rate', 8, 2)->default(0);
            $table->decimal('bounce_rate', 8, 2)->default(0);
            $table->decimal('deferred_rate', 8, 2)->default(0);
            $table->timestamps();
            $table->unique(['server_id', 'date']);
        });

        Schema::create('mail_monthly_stats', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('server_id')->constrained('mail_servers')->cascadeOnDelete();
            $table->string('month', 7)->index();
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('bounced_count')->default(0);
            $table->unsignedInteger('deferred_count')->default(0);
            $table->unsignedInteger('reject_count')->default(0);
            $table->decimal('failure_rate', 8, 2)->default(0);
            $table->decimal('bounce_rate', 8, 2)->default(0);
            $table->decimal('deferred_rate', 8, 2)->default(0);
            $table->timestamps();
            $table->unique(['server_id', 'month']);
        });

        Schema::create('queue_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('server_id')->constrained('mail_servers')->cascadeOnDelete();
            $table->timestamp('captured_at')->index();
            $table->unsignedInteger('queue_count')->default(0);
            $table->longText('raw_output')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('queue_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('server_id')->constrained('mail_servers')->cascadeOnDelete();
            $table->string('queue_id', 64)->index();
            $table->string('sender')->nullable()->index();
            $table->string('recipient')->nullable()->index();
            $table->string('recipient_domain')->nullable()->index();
            $table->unsignedInteger('size')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->text('reason')->nullable();
            $table->text('raw_block')->nullable();
            $table->timestamps();
            $table->unique(['server_id', 'queue_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_items');
        Schema::dropIfExists('queue_snapshots');
        Schema::dropIfExists('mail_monthly_stats');
        Schema::dropIfExists('mail_daily_stats');
    }
};
