<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('suppressions', function (Blueprint $table): void {
            $table->id();
            $table->enum('type', ['email', 'domain'])->index();
            $table->string('value')->unique();
            $table->string('reason')->nullable();
            $table->enum('source', ['manual', 'csv', 'bounce', 'complaint', 'api'])->default('manual')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('blocklists', function (Blueprint $table): void {
            $table->id();
            $table->enum('type', ['email', 'domain', 'ip', 'cidr'])->index();
            $table->string('value')->unique();
            $table->enum('action', ['reject', 'discard', 'hold', 'tag'])->default('reject')->index();
            $table->string('reason')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('allowlists', function (Blueprint $table): void {
            $table->id();
            $table->enum('type', ['email', 'domain', 'ip', 'cidr'])->index();
            $table->string('value')->unique();
            $table->string('reason')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('dns_checks', function (Blueprint $table): void {
            $table->id();
            $table->string('domain')->index();
            $table->text('mx_result')->nullable();
            $table->text('spf_result')->nullable();
            $table->text('dkim_mail_result')->nullable();
            $table->text('dkim_default_result')->nullable();
            $table->text('dmarc_result')->nullable();
            $table->text('a_result')->nullable();
            $table->text('ptr_result')->nullable();
            $table->unsignedTinyInteger('score')->default(0);
            $table->timestamp('checked_at')->index();
            $table->timestamps();
        });

        Schema::create('health_checks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('server_id')->nullable()->constrained('mail_servers')->nullOnDelete();
            $table->string('check_type', 32)->index();
            $table->string('status', 32)->index();
            $table->unsignedTinyInteger('score')->default(0);
            $table->string('message')->nullable();
            $table->longText('raw_output')->nullable();
            $table->timestamp('checked_at')->index();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action')->index();
            $table->string('target_type')->nullable()->index();
            $table->string('target_id')->nullable()->index();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });

        Schema::create('settings', function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->longText('value')->nullable();
            $table->string('type')->default('string');
            $table->string('description')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('health_checks');
        Schema::dropIfExists('dns_checks');
        Schema::dropIfExists('allowlists');
        Schema::dropIfExists('blocklists');
        Schema::dropIfExists('suppressions');
    }
};
