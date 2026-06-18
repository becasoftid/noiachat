<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class ValidateTenantReadinessCommand extends Command
{
    protected $signature = 'noiachat:tenant-validate
        {--backup-path= : Ruta de backups a validar. Por defecto storage/app/backups}
        {--skip-backup : Omite validacion de backup reciente}';

    protected $description = 'Valida que la instalacion este lista para operar multiempresa/multisede.';

    /** @var array<int, string> */
    private array $errors = [];

    /** @var array<int, string> */
    private array $warnings = [];

    public function handle(): int
    {
        $this->info('Validando preparacion multiempresa/multisede...');

        $this->validateTenantTables();
        $this->validateRoles();
        $this->validateTenantData();
        $this->validateOperationalTenantColumns();
        $this->validateMembershipConsistency();

        if (! $this->option('skip-backup')) {
            $this->validateLatestBackup();
        }

        $this->newLine();
        $this->line('Resumen:');
        $this->line('- Errores: '.count($this->errors));
        $this->line('- Advertencias: '.count($this->warnings));

        foreach ($this->warnings as $warning) {
            $this->warn($warning);
        }

        foreach ($this->errors as $error) {
            $this->error($error);
        }

        if ($this->errors !== []) {
            return self::FAILURE;
        }

        $this->info('Validacion multiempresa completada.');

        return self::SUCCESS;
    }

    private function validateTenantTables(): void
    {
        foreach (['companies', 'branches', 'memberships'] as $table) {
            $this->assert(Schema::hasTable($table), "Falta tabla requerida: {$table}");
        }
    }

    private function validateRoles(): void
    {
        if (! Schema::hasTable('roles')) {
            $this->failCheck('Falta tabla requerida: roles');

            return;
        }

        foreach (['super_admin', 'company_admin', 'branch_manager', 'operator', 'auditor'] as $role) {
            $this->assert(
                DB::table('roles')->where('name', $role)->exists(),
                "Falta rol requerido: {$role}",
            );
        }
    }

    private function validateTenantData(): void
    {
        if (! Schema::hasTable('companies') || ! Schema::hasTable('branches') || ! Schema::hasTable('memberships')) {
            return;
        }

        $this->assert(DB::table('companies')->where('status', 'active')->exists(), 'No hay empresas activas.');
        $this->assert(DB::table('branches')->where('is_active', true)->exists(), 'No hay sedes activas.');
        $this->assert(DB::table('memberships')->where('is_active', true)->exists(), 'No hay membresias activas.');

        $companiesWithoutBranches = DB::table('companies')
            ->leftJoin('branches', 'branches.company_id', '=', 'companies.id')
            ->whereNull('branches.id')
            ->count();

        if ($companiesWithoutBranches > 0) {
            $this->warnCheck("Hay {$companiesWithoutBranches} empresas sin sedes.");
        }
    }

    private function validateOperationalTenantColumns(): void
    {
        foreach ($this->operationalTables() as $table) {
            if (! Schema::hasTable($table)) {
                $this->failCheck("Falta tabla operativa esperada: {$table}");

                continue;
            }

            $this->assert(Schema::hasColumn($table, 'company_id'), "{$table} no tiene company_id.");
            $this->assert(Schema::hasColumn($table, 'branch_id'), "{$table} no tiene branch_id.");

            if (Schema::hasColumn($table, 'company_id')) {
                $nullCompanyRows = DB::table($table)->whereNull('company_id')->count();
                $this->assert($nullCompanyRows === 0, "{$table} tiene {$nullCompanyRows} filas sin company_id.");
            }
        }
    }

    private function validateMembershipConsistency(): void
    {
        if (! Schema::hasTable('memberships') || ! Schema::hasTable('branches')) {
            return;
        }

        $crossCompanyMemberships = DB::table('memberships')
            ->join('branches', 'branches.id', '=', 'memberships.branch_id')
            ->whereColumn('memberships.company_id', '!=', 'branches.company_id')
            ->count();

        $this->assert($crossCompanyMemberships === 0, "{$crossCompanyMemberships} membresias apuntan a sedes de otra empresa.");
    }

    private function validateLatestBackup(): void
    {
        $backupRoot = $this->backupRoot();
        $directories = collect(File::directories($backupRoot))
            ->filter(fn (string $directory): bool => str_starts_with(basename($directory), 'noiachat_'))
            ->sortByDesc(fn (string $directory): int => File::lastModified($directory))
            ->values();

        if ($directories->isEmpty()) {
            $this->failCheck("No hay backups en {$backupRoot}.");

            return;
        }

        $latestBackup = $directories->first();
        $manifestPath = $latestBackup.DIRECTORY_SEPARATOR.'manifest.json';

        $this->assert(File::exists($manifestPath), "El backup mas reciente no tiene manifest.json: {$latestBackup}");

        if (! File::exists($manifestPath)) {
            return;
        }

        $manifest = json_decode(File::get($manifestPath), true);
        $mode = data_get($manifest, 'mode');

        foreach (['environment', 'mode', 'database_connection', 'generated_at'] as $key) {
            $this->assert(filled(data_get($manifest, $key)), "El manifest del backup no tiene {$key}.");
        }

        if (in_array($mode, ['all', 'database'], true)) {
            $databaseFiles = collect(File::files($latestBackup))
                ->filter(fn ($file): bool => $file->getFilename() === 'database.sqlite' || str_ends_with($file->getFilename(), '.sql'));
            $this->assert($databaseFiles->isNotEmpty(), 'El backup no contiene copia/dump de base de datos.');
        }

        if (in_array($mode, ['all', 'storage'], true)) {
            $storageFiles = collect(File::files($latestBackup))
                ->filter(fn ($file): bool => str_starts_with($file->getFilename(), 'storage_public_') && str_ends_with($file->getFilename(), '.zip'));
            $this->assert($storageFiles->isNotEmpty(), 'El backup no contiene ZIP de storage publico.');
        }
    }

    private function backupRoot(): string
    {
        $path = $this->option('backup-path');

        if (! $path) {
            return storage_path('app/backups');
        }

        $path = (string) $path;

        return str_starts_with($path, DIRECTORY_SEPARATOR) ? $path : base_path($path);
    }

    /**
     * @return array<int, string>
     */
    private function operationalTables(): array
    {
        return [
            'channels',
            'contacts',
            'contact_channels',
            'contact_consents',
            'contact_blacklist',
            'conversations',
            'message_templates',
            'media_files',
            'messages',
            'message_events',
            'message_attachments',
            'inbound_messages',
            'opt_out_requests',
            'provider_logs',
            'audit_logs',
        ];
    }

    private function assert(bool $condition, string $message): void
    {
        if (! $condition) {
            $this->failCheck($message);
        }
    }

    private function failCheck(string $message): void
    {
        $this->errors[] = $message;
    }

    private function warnCheck(string $message): void
    {
        $this->warnings[] = $message;
    }
}
