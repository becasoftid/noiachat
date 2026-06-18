<?php

use App\Modules\Tenancy\Application\Support\TenancyDefaults;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, bool> */
    private array $tables = [
        'channels' => true,
        'contacts' => true,
        'contact_channels' => true,
        'contact_consents' => true,
        'contact_blacklist' => true,
        'conversations' => true,
        'message_templates' => true,
        'media_files' => true,
        'messages' => true,
        'message_events' => true,
        'message_attachments' => true,
        'inbound_messages' => true,
        'opt_out_requests' => true,
        'provider_logs' => true,
        'audit_logs' => true,
    ];

    public function up(): void
    {
        $companyId = TenancyDefaults::companyId();
        $branchId = TenancyDefaults::branchId($companyId);

        foreach ($this->tables as $table => $usesBranch) {
            $this->addTenantColumns($table, $usesBranch);
            $this->backfillTenantColumns($table, $companyId, $usesBranch ? $branchId : null);
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $table => $usesBranch) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $table) use ($usesBranch): void {
                if ($usesBranch && Schema::hasColumn($table->getTable(), 'branch_id')) {
                    $table->dropColumn('branch_id');
                }

                if (Schema::hasColumn($table->getTable(), 'company_id')) {
                    $table->dropColumn('company_id');
                }
            });
        }
    }

    private function addTenantColumns(string $tableName, bool $usesBranch): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName, $usesBranch): void {
            if (! Schema::hasColumn($tableName, 'company_id')) {
                $table->uuid('company_id')->nullable()->index();
            }

            if ($usesBranch && ! Schema::hasColumn($tableName, 'branch_id')) {
                $table->uuid('branch_id')->nullable()->index();
            }
        });
    }

    private function backfillTenantColumns(string $tableName, string $companyId, ?string $branchId): void
    {
        if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'company_id')) {
            return;
        }

        DB::table($tableName)
            ->whereNull('company_id')
            ->update(['company_id' => $companyId]);

        if ($branchId !== null && Schema::hasColumn($tableName, 'branch_id')) {
            DB::table($tableName)
                ->whereNull('branch_id')
                ->update(['branch_id' => $branchId]);
        }
    }
};
