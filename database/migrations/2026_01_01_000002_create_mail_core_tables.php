<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mail_servers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('hostname')->nullable();
            $table->string('container_name')->default('mailserver');
            $table->string('domain')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        DB::table('mail_servers')->insert([
            'name' => 'Local docker-mailserver',
            'hostname' => env('MAIL_HOSTNAME', 'mail.example.com'),
            'container_name' => env('MAIL_CONTAINER', 'mailserver'),
            'domain' => env('MAIL_DOMAIN', 'example.com'),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('mail_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('server_id')->constrained('mail_servers')->cascadeOnDelete();
            $table->timestamp('event_time')->index();
            $table->string('queue_id', 64)->nullable()->index();
            $table->string('event_type', 32)->index();
            $table->string('status', 32)->nullable()->index();
            $table->string('sender')->nullable()->index();
            $table->string('recipient')->nullable()->index();
            $table->string('recipient_domain')->nullable()->index();
            $table->string('client_ip', 45)->nullable()->index();
            $table->string('relay')->nullable();
            $table->string('dsn')->nullable();
            $table->string('delay')->nullable();
            $table->text('message')->nullable();
            $table->text('raw_line');
            $table->string('raw_hash', 64)->index();
            $table->timestamp('created_at')->nullable();
            $table->unique(['server_id', 'raw_hash'], 'mail_events_dedupe');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_events');
        Schema::dropIfExists('mail_servers');
    }
};
