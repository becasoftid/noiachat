<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('message_templates', function (Blueprint $table): void {
            $table->string('meta_template_id')->nullable()->index();
            $table->string('meta_status', 60)->nullable()->index();
            $table->string('meta_category', 80)->nullable();
            $table->json('meta_payload')->nullable();
            $table->timestamp('synced_at')->nullable()->index();
        });

        Schema::table('template_versions', function (Blueprint $table): void {
            $table->json('components')->nullable();
            $table->unsignedInteger('variable_count')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('template_versions', function (Blueprint $table): void {
            $table->dropColumn(['components', 'variable_count']);
        });

        Schema::table('message_templates', function (Blueprint $table): void {
            $table->dropColumn(['meta_template_id', 'meta_status', 'meta_category', 'meta_payload', 'synced_at']);
        });
    }
};
