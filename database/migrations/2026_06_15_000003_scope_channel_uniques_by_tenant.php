<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channels', function (Blueprint $table): void {
            $table->dropUnique('channels_name_unique');
            $table->dropUnique('channels_slug_unique');
            $table->unique(['company_id', 'branch_id', 'name'], 'channels_company_branch_name_unique');
            $table->unique(['company_id', 'branch_id', 'slug'], 'channels_company_branch_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table): void {
            $table->dropUnique('channels_company_branch_name_unique');
            $table->dropUnique('channels_company_branch_slug_unique');
            $table->unique('name');
            $table->unique('slug');
        });
    }
};
