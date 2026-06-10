<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use ZipArchive;

class NoiaChatBackupTest extends TestCase
{
    protected string $backupPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupPath = storage_path('framework/testing-backups');
        File::deleteDirectory($this->backupPath);
        File::ensureDirectoryExists($this->backupPath);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->backupPath);
        File::delete(storage_path('framework/testing-noiachat.sqlite'));
        File::delete(storage_path('app/public/testing-backup.txt'));

        parent::tearDown();
    }

    public function test_backup_command_copies_sqlite_database(): void
    {
        $database = storage_path('framework/testing-noiachat.sqlite');
        File::put($database, 'sqlite-content');

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $database,
        ]);

        $exitCode = Artisan::call('noiachat:backup', [
            '--only' => 'database',
            '--path' => $this->backupPath,
            '--retention' => 1,
        ]);

        $this->assertSame(0, $exitCode);

        $backupDirectory = collect(File::directories($this->backupPath))->first();
        $this->assertNotNull($backupDirectory);
        $this->assertFileExists($backupDirectory.'/database.sqlite');
        $this->assertFileExists($backupDirectory.'/manifest.json');
        $this->assertSame('sqlite-content', File::get($backupDirectory.'/database.sqlite'));
    }

    public function test_backup_command_zips_public_storage(): void
    {
        File::ensureDirectoryExists(storage_path('app/public'));
        File::put(storage_path('app/public/testing-backup.txt'), 'storage-content');

        $exitCode = Artisan::call('noiachat:backup', [
            '--only' => 'storage',
            '--path' => $this->backupPath,
            '--retention' => 1,
        ]);

        $this->assertSame(0, $exitCode);

        $backupDirectory = collect(File::directories($this->backupPath))->first();
        $zipPath = collect(File::files($backupDirectory))
            ->first(fn ($file) => str_ends_with($file->getFilename(), '.zip'))
            ?->getPathname();

        $this->assertNotNull($zipPath);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($zipPath));
        $this->assertSame('storage-content', $zip->getFromName('testing-backup.txt'));
        $zip->close();
    }
}
