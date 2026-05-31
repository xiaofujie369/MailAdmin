<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function addColumnIfMissing(string $table, string $column, callable $callback): void
    {
        if (!Schema::hasColumn($table, $column)) {
            Schema::table($table, function (Blueprint $t) use ($column, $callback) {
                $callback($t);
            });
        }
    }

    public function up(): void
    {
        if (!Schema::hasTable('mail_events')) {
            Schema::create('mail_events', function (Blueprint $table) {
                $table->id();
                $table->string('event_type')->nullable()->index();
                $table->string('queue_id')->nullable()->index();
                $table->string('message_id')->nullable()->index();
                $table->string('sender')->nullable()->index();
                $table->string('recipient')->nullable()->index();
                $table->string('recipient_domain')->nullable()->index();
                $table->string('client_ip')->nullable()->index();
                $table->string('status')->nullable()->index();
                $table->string('raw_hash')->nullable()->unique();
                $table->text('raw_line')->nullable();
                $table->timestamp('occurred_at')->nullable()->index();
                $table->timestamps();
            });
        } else {
            $this->addColumnIfMissing('mail_events', 'event_type', fn (Blueprint $t) => $t->string('event_type')->nullable()->index());
            $this->addColumnIfMissing('mail_events', 'queue_id', fn (Blueprint $t) => $t->string('queue_id')->nullable()->index());
            $this->addColumnIfMissing('mail_events', 'message_id', fn (Blueprint $t) => $t->string('message_id')->nullable()->index());
            $this->addColumnIfMissing('mail_events', 'sender', fn (Blueprint $t) => $t->string('sender')->nullable()->index());
            $this->addColumnIfMissing('mail_events', 'recipient', fn (Blueprint $t) => $t->string('recipient')->nullable()->index());
            $this->addColumnIfMissing('mail_events', 'recipient_domain', fn (Blueprint $t) => $t->string('recipient_domain')->nullable()->index());
            $this->addColumnIfMissing('mail_events', 'client_ip', fn (Blueprint $t) => $t->string('client_ip')->nullable()->index());
            $this->addColumnIfMissing('mail_events', 'status', fn (Blueprint $t) => $t->string('status')->nullable()->index());
            $this->addColumnIfMissing('mail_events', 'raw_hash', fn (Blueprint $t) => $t->string('raw_hash')->nullable()->unique());
            $this->addColumnIfMissing('mail_events', 'raw_line', fn (Blueprint $t) => $t->text('raw_line')->nullable());
            $this->addColumnIfMissing('mail_events', 'occurred_at', fn (Blueprint $t) => $t->timestamp('occurred_at')->nullable()->index());
        }

        if (!Schema::hasTable('mail_daily_stats')) {
            Schema::create('mail_daily_stats', function (Blueprint $table) {
                $table->id();
                $table->date('date')->unique();
                $table->unsignedInteger('sent_count')->default(0);
                $table->unsignedInteger('bounced_count')->default(0);
                $table->unsignedInteger('deferred_count')->default(0);
                $table->unsignedInteger('reject_count')->default(0);
                $table->decimal('failure_rate', 8, 2)->default(0);
                $table->timestamps();
            });
        } else {
            $this->addColumnIfMissing('mail_daily_stats', 'date', fn (Blueprint $t) => $t->date('date')->nullable()->index());
            $this->addColumnIfMissing('mail_daily_stats', 'sent_count', fn (Blueprint $t) => $t->unsignedInteger('sent_count')->default(0));
            $this->addColumnIfMissing('mail_daily_stats', 'bounced_count', fn (Blueprint $t) => $t->unsignedInteger('bounced_count')->default(0));
            $this->addColumnIfMissing('mail_daily_stats', 'deferred_count', fn (Blueprint $t) => $t->unsignedInteger('deferred_count')->default(0));
            $this->addColumnIfMissing('mail_daily_stats', 'reject_count', fn (Blueprint $t) => $t->unsignedInteger('reject_count')->default(0));
            $this->addColumnIfMissing('mail_daily_stats', 'failure_rate', fn (Blueprint $t) => $t->decimal('failure_rate', 8, 2)->default(0));
        }

        if (!Schema::hasTable('mail_queue_snapshots')) {
            Schema::create('mail_queue_snapshots', function (Blueprint $table) {
                $table->id();
                $table->string('queue_name')->nullable();
                $table->unsignedInteger('pending')->default(0);
                $table->unsignedInteger('deferred')->default(0);
                $table->unsignedInteger('active')->default(0);
                $table->unsignedInteger('failed')->default(0);
                $table->json('raw')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('system_health_checks')) {
            Schema::create('system_health_checks', function (Blueprint $table) {
                $table->id();
                $table->string('check_type')->index();
                $table->string('status')->default('unknown')->index();
                $table->unsignedInteger('score')->default(0);
                $table->text('message')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_queue_snapshots');
        Schema::dropIfExists('system_health_checks');
    }
};
