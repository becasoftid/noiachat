<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('label');
            $table->timestamps();
        });

        Schema::create('user_roles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'role_id']);
        });

        Schema::create('channels', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('contacts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('full_name')->index();
            $table->string('email')->nullable()->index();
            $table->string('primary_phone', 30)->index();
            $table->string('status')->default('active')->index();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contact_channels', function (Blueprint $table): void {
            $table->id();
            $table->uuid('contact_id');
            $table->foreign('contact_id')->references('id')->on('contacts')->cascadeOnDelete();
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->string('phone', 30)->index();
            $table->boolean('is_primary')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['channel_id', 'phone']);
        });

        Schema::create('contact_consents', function (Blueprint $table): void {
            $table->id();
            $table->uuid('contact_id');
            $table->foreign('contact_id')->references('id')->on('contacts')->cascadeOnDelete();
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->string('status')->index();
            $table->string('source')->index();
            $table->foreignId('granted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('revoked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['contact_id', 'channel_id', 'status']);
        });

        Schema::create('contact_blacklist', function (Blueprint $table): void {
            $table->id();
            $table->uuid('contact_id');
            $table->foreign('contact_id')->references('id')->on('contacts')->cascadeOnDelete();
            $table->foreignId('channel_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at');
            $table->unique(['contact_id', 'channel_id']);
        });

        Schema::create('conversations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('contact_id');
            $table->foreign('contact_id')->references('id')->on('contacts')->cascadeOnDelete();
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('open')->index();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();
            $table->index(['contact_id', 'channel_id', 'status']);
        });

        Schema::create('message_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->string('name')->index();
            $table->string('external_template_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('template_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('message_template_id')->constrained('message_templates')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('language', 10)->default('es');
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['message_template_id', 'version']);
        });

        Schema::table('message_templates', function (Blueprint $table): void {
            $table->foreign('current_version_id')->references('id')->on('template_versions')->nullOnDelete();
        });

        Schema::create('media_files', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('disk');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 120)->index();
            $table->unsignedBigInteger('size_bytes');
            $table->string('extension', 20);
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('messages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('contact_id');
            $table->foreign('contact_id')->references('id')->on('contacts')->cascadeOnDelete();
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->uuid('conversation_id')->nullable();
            $table->foreign('conversation_id')->references('id')->on('conversations')->nullOnDelete();
            $table->foreignId('message_template_id')->nullable()->constrained('message_templates')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->index();
            $table->string('status')->index();
            $table->string('provider_message_id')->nullable()->index();
            $table->text('body')->nullable();
            $table->unsignedInteger('retry_count')->default(0);
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('message_attachments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('message_id');
            $table->foreign('message_id')->references('id')->on('messages')->cascadeOnDelete();
            $table->uuid('media_file_id');
            $table->foreign('media_file_id')->references('id')->on('media_files')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['message_id', 'media_file_id']);
        });

        Schema::create('message_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('message_id');
            $table->foreign('message_id')->references('id')->on('messages')->cascadeOnDelete();
            $table->string('status')->index();
            $table->string('event_type')->nullable()->index();
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
        });

        Schema::create('inbound_messages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('contact_id')->nullable();
            $table->foreign('contact_id')->references('id')->on('contacts')->nullOnDelete();
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->uuid('conversation_id')->nullable();
            $table->foreign('conversation_id')->references('id')->on('conversations')->nullOnDelete();
            $table->string('provider_message_id')->nullable()->index();
            $table->string('from_phone', 30)->index();
            $table->text('body')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('opt_out_requests', function (Blueprint $table): void {
            $table->id();
            $table->uuid('inbound_message_id');
            $table->foreign('inbound_message_id')->references('id')->on('inbound_messages')->cascadeOnDelete();
            $table->uuid('contact_id')->nullable();
            $table->foreign('contact_id')->references('id')->on('contacts')->nullOnDelete();
            $table->foreignId('channel_id')->nullable()->constrained()->nullOnDelete();
            $table->string('keyword');
            $table->timestamp('requested_at');
            $table->timestamps();
            $table->unique('inbound_message_id');
        });

        Schema::create('provider_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('provider')->index();
            $table->string('direction')->index();
            $table->string('event_type')->nullable()->index();
            $table->string('external_event_id')->nullable()->unique();
            $table->uuid('message_id')->nullable();
            $table->foreign('message_id')->references('id')->on('messages')->nullOnDelete();
            $table->uuid('inbound_message_id')->nullable();
            $table->foreign('inbound_message_id')->references('id')->on('inbound_messages')->nullOnDelete();
            $table->json('payload');
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action')->index();
            $table->string('module')->index();
            $table->string('target_type')->nullable()->index();
            $table->string('target_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('old_values_json')->nullable();
            $table->json('new_values_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('provider_logs');
        Schema::dropIfExists('opt_out_requests');
        Schema::dropIfExists('inbound_messages');
        Schema::dropIfExists('message_events');
        Schema::dropIfExists('message_attachments');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('media_files');
        Schema::table('message_templates', function (Blueprint $table): void {
            $table->dropForeign(['current_version_id']);
        });
        Schema::dropIfExists('template_versions');
        Schema::dropIfExists('message_templates');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('contact_blacklist');
        Schema::dropIfExists('contact_consents');
        Schema::dropIfExists('contact_channels');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('channels');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('roles');
    }
};
