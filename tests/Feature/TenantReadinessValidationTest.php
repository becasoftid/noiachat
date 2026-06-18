<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class TenantReadinessValidationTest extends TestCase
{
    use RefreshDatabase;

    private string $backupPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupPath = storage_path('framework/testing-tenant-backups');
        File::deleteDirectory($this->backupPath);
        File::ensureDirectoryExists($this->backupPath);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->backupPath);

        parent::tearDown();
    }

    public function test_tenant_validation_passes_for_seeded_installation_without_backup_check(): void
    {
        $this->seed(DatabaseSeeder::class);

        $exitCode = Artisan::call('noiachat:tenant-validate', [
            '--skip-backup' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Validacion multiempresa completada.', Artisan::output());
    }

    public function test_tenant_validation_accepts_latest_backup_manifest(): void
    {
        $this->seed(DatabaseSeeder::class);
        $backupDirectory = $this->backupPath.'/noiachat_20260616_120000';
        File::ensureDirectoryExists($backupDirectory);
        File::put($backupDirectory.'/manifest.json', json_encode([
            'app' => 'NoiaChat',
            'environment' => 'testing',
            'timestamp' => '20260616_120000',
            'mode' => 'all',
            'database_connection' => 'sqlite',
            'generated_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT));
        File::put($backupDirectory.'/database.sqlite', 'sqlite-content');
        File::put($backupDirectory.'/storage_public_20260616_120000.zip', 'zip-content');

        $exitCode = Artisan::call('noiachat:tenant-validate', [
            '--backup-path' => $this->backupPath,
        ]);

        $this->assertSame(0, $exitCode);
    }

    public function test_tenant_validation_fails_when_operational_rows_do_not_have_company(): void
    {
        $this->seed(DatabaseSeeder::class);
        DB::table('contacts')->limit(1)->update(['company_id' => null]);

        $exitCode = Artisan::call('noiachat:tenant-validate', [
            '--skip-backup' => true,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('contacts tiene 1 filas sin company_id.', Artisan::output());
    }
}
