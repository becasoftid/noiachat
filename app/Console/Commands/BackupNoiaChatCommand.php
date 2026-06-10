<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;
use ZipArchive;

class BackupNoiaChatCommand extends Command
{
    protected $signature = 'noiachat:backup
        {--only=all : Valores permitidos: all, database, storage}
        {--retention=14 : Dias de retencion local de backups}
        {--path= : Ruta destino opcional para los backups}';

    protected $description = 'Genera backup local de base de datos y archivos publicos de NoiaChat.';

    public function handle(): int
    {
        $only = (string) $this->option('only');

        if (! in_array($only, ['all', 'database', 'storage'], true)) {
            $this->error('La opcion --only debe ser all, database o storage.');

            return self::FAILURE;
        }

        $backupRoot = $this->backupRoot();
        File::ensureDirectoryExists($backupRoot);

        $timestamp = now()->format('Ymd_His');
        $backupDir = $backupRoot.DIRECTORY_SEPARATOR.'noiachat_'.$timestamp;
        File::ensureDirectoryExists($backupDir);

        try {
            if (in_array($only, ['all', 'database'], true)) {
                $this->backupDatabase($backupDir, $timestamp);
            }

            if (in_array($only, ['all', 'storage'], true)) {
                $this->backupStorage($backupDir, $timestamp);
            }

            $this->writeManifest($backupDir, $timestamp, $only);
            $this->pruneOldBackups($backupRoot, (int) $this->option('retention'));
        } catch (Throwable $exception) {
            File::deleteDirectory($backupDir);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Backup generado en {$backupDir}");

        return self::SUCCESS;
    }

    private function backupRoot(): string
    {
        $path = $this->option('path');

        if (! $path) {
            return storage_path('app/backups');
        }

        $path = (string) $path;

        return str_starts_with($path, DIRECTORY_SEPARATOR) ? $path : base_path($path);
    }

    private function backupDatabase(string $backupDir, string $timestamp): void
    {
        $connectionName = config('database.default');
        $connection = config("database.connections.{$connectionName}");
        $driver = data_get($connection, 'driver');

        match ($driver) {
            'sqlite' => $this->backupSqlite($backupDir, $connection),
            'mysql', 'mariadb' => $this->dumpDatabase($backupDir, $timestamp, ...$this->mysqlDumpCommand($connection)),
            'pgsql' => $this->dumpDatabase($backupDir, $timestamp, ...$this->postgresDumpCommand($connection)),
            default => throw new RuntimeException("Driver de base de datos no soportado para backup: {$driver}"),
        };
    }

    private function backupSqlite(string $backupDir, array $connection): void
    {
        $database = (string) data_get($connection, 'database');
        $source = $database === ':memory:' ? null : $database;

        if (! $source || ! File::exists($source)) {
            throw new RuntimeException('No se encontro el archivo SQLite para respaldar.');
        }

        File::copy($source, $backupDir.DIRECTORY_SEPARATOR.'database.sqlite');
    }

    private function dumpDatabase(string $backupDir, string $timestamp, array $command, array $environment = []): void
    {
        $target = $backupDir.DIRECTORY_SEPARATOR."database_{$timestamp}.sql";
        $process = new Process($command);
        $process->setTimeout(300);
        $process->setEnv($environment);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(trim($process->getErrorOutput()) ?: 'No fue posible generar el dump de base de datos.');
        }

        File::put($target, $process->getOutput());
    }

    private function mysqlDumpCommand(array $connection): array
    {
        $command = array_values(array_filter([
            'mysqldump',
            '--single-transaction',
            '--skip-comments',
            '-h'.data_get($connection, 'host', '127.0.0.1'),
            '-P'.data_get($connection, 'port', 3306),
            '-u'.data_get($connection, 'username'),
            data_get($connection, 'database'),
        ]));

        $environment = filled(data_get($connection, 'password'))
            ? ['MYSQL_PWD' => data_get($connection, 'password')]
            : [];

        return [$command, $environment];
    }

    private function postgresDumpCommand(array $connection): array
    {
        return [[
            'pg_dump',
            '-h',
            (string) data_get($connection, 'host', '127.0.0.1'),
            '-p',
            (string) data_get($connection, 'port', 5432),
            '-U',
            (string) data_get($connection, 'username'),
            '-d',
            (string) data_get($connection, 'database'),
        ], filled(data_get($connection, 'password')) ? ['PGPASSWORD' => data_get($connection, 'password')] : []];
    }

    private function backupStorage(string $backupDir, string $timestamp): void
    {
        $source = storage_path('app/public');

        if (! File::isDirectory($source)) {
            File::ensureDirectoryExists($source);
        }

        $zipPath = $backupDir.DIRECTORY_SEPARATOR."storage_public_{$timestamp}.zip";
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No fue posible crear el archivo ZIP de storage.');
        }

        foreach (File::allFiles($source) as $file) {
            $zip->addFile($file->getPathname(), Str::after($file->getPathname(), $source.DIRECTORY_SEPARATOR));
        }

        $zip->close();
    }

    private function writeManifest(string $backupDir, string $timestamp, string $only): void
    {
        File::put($backupDir.DIRECTORY_SEPARATOR.'manifest.json', json_encode([
            'app' => config('app.name'),
            'environment' => app()->environment(),
            'timestamp' => $timestamp,
            'mode' => $only,
            'database_connection' => config('database.default'),
            'generated_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    }

    private function pruneOldBackups(string $backupRoot, int $retentionDays): void
    {
        if ($retentionDays <= 0) {
            return;
        }

        $threshold = now()->subDays($retentionDays)->getTimestamp();

        foreach (File::directories($backupRoot) as $directory) {
            if (str_starts_with(basename($directory), 'noiachat_') && File::lastModified($directory) < $threshold) {
                File::deleteDirectory($directory);
            }
        }
    }
}
