<?php

namespace App\Modules\Reports\Application\Services;

use App\Modules\Contacts\Infrastructure\Persistence\Models\Channel;
use App\Modules\Messaging\Infrastructure\Persistence\Models\ProviderLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class HealthMonitorService
{
    public function checks(): Collection
    {
        return collect([
            $this->failedJobs(),
            $this->pendingJobs(),
            $this->diskUsage(),
            $this->tokenRotation(),
            $this->webhookActivity(),
            $this->latestBackup(),
            $this->recentLogErrors(),
        ]);
    }

    public function summary(): array
    {
        $checks = $this->checks();

        return [
            'status' => $this->overallStatus($checks),
            'checks' => $checks,
            'critical_count' => $checks->where('status', 'critical')->count(),
            'warning_count' => $checks->where('status', 'warning')->count(),
            'ok_count' => $checks->where('status', 'ok')->count(),
        ];
    }

    private function failedJobs(): array
    {
        $count = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;

        return [
            'key' => 'failed_jobs',
            'label' => 'Jobs fallidos',
            'status' => $count > 0 ? 'critical' : 'ok',
            'value' => $count,
            'detail' => $count > 0 ? "{$count} jobs fallidos registrados." : 'No hay jobs fallidos.',
            'action' => $count > 0 ? 'Revisar /failures, corregir causa y reintentar o descartar jobs.' : 'Sin accion requerida.',
        ];
    }

    private function pendingJobs(): array
    {
        $count = Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0;
        $max = (int) config('noiachat.health.max_pending_jobs', 50);

        return [
            'key' => 'pending_jobs',
            'label' => 'Cola pendiente',
            'status' => $count > $max ? 'warning' : 'ok',
            'value' => $count,
            'detail' => "{$count} jobs pendientes en cola.",
            'action' => $count > $max ? 'Verificar worker Supervisor y procesamiento de cola.' : 'Mantener worker activo.',
        ];
    }

    private function diskUsage(): array
    {
        $path = storage_path();
        $free = @disk_free_space($path);
        $total = @disk_total_space($path);

        if ($free === false || $total === false || $total <= 0) {
            return [
                'key' => 'disk',
                'label' => 'Disco storage',
                'status' => 'warning',
                'value' => 'N/D',
                'detail' => 'No se pudo leer el espacio disponible.',
                'action' => 'Validar permisos del proceso PHP sobre storage.',
            ];
        }

        $freePercent = round(($free / $total) * 100, 1);
        $critical = (float) config('noiachat.health.disk_critical_percent', 5);
        $warning = (float) config('noiachat.health.disk_warning_percent', 15);
        $status = $freePercent <= $critical ? 'critical' : ($freePercent <= $warning ? 'warning' : 'ok');

        return [
            'key' => 'disk',
            'label' => 'Disco storage',
            'status' => $status,
            'value' => $freePercent.'%',
            'detail' => $this->bytes($free).' libres de '.$this->bytes($total).'.',
            'action' => $status === 'ok' ? 'Sin accion requerida.' : 'Liberar logs/backups antiguos o ampliar disco.',
        ];
    }

    private function webhookActivity(): array
    {
        $latest = Schema::hasTable('provider_logs')
            ? ProviderLog::query()
                ->where('provider', 'whatsapp_cloud')
                ->where('direction', 'inbound')
                ->where('event_type', 'webhook')
                ->latest()
                ->first()
            : null;

        if (! $latest) {
            return [
                'key' => 'webhook',
                'label' => 'Webhook WhatsApp',
                'status' => 'warning',
                'value' => 'Sin eventos',
                'detail' => 'No hay webhooks recibidos registrados.',
                'action' => 'Validar endpoint en Meta y enviar evento de prueba.',
            ];
        }

        $maxAge = (int) config('noiachat.health.max_webhook_age_hours', 24);
        $ageHours = $latest->created_at->diffInHours(now());
        $status = $ageHours > $maxAge ? 'warning' : 'ok';

        return [
            'key' => 'webhook',
            'label' => 'Webhook WhatsApp',
            'status' => $status,
            'value' => $latest->created_at->diffForHumans(),
            'detail' => 'Ultimo webhook: '.$latest->created_at->format('Y-m-d H:i:s').'.',
            'action' => $status === 'ok' ? 'Recepcion reciente registrada.' : 'Verificar suscripcion de Meta, dominio HTTPS y logs.',
        ];
    }

    private function tokenRotation(): array
    {
        $channels = Schema::hasTable('channels')
            ? Channel::query()
                ->where('slug', 'whatsapp')
                ->whereNotNull('settings')
                ->get()
                ->filter(fn (Channel $channel): bool => filled(data_get($channel->settings, 'access_token')))
            : collect();

        if ($channels->isEmpty()) {
            return [
                'key' => 'token_rotation',
                'label' => 'Rotacion token WhatsApp',
                'status' => 'warning',
                'value' => 'Sin tokens',
                'detail' => 'No hay tokens WhatsApp configurados en canales.',
                'action' => 'Configurar token permanente por canal o confirmar fallback por .env.',
            ];
        }

        $warningDays = (int) config('noiachat.health.token_expiry_warning_days', 14);
        $expired = 0;
        $expiring = 0;
        $missingGovernance = 0;
        $nearestExpiration = null;

        foreach ($channels as $channel) {
            $expiresAt = data_get($channel->settings, 'access_token_expires_at');
            $responsible = data_get($channel->settings, 'access_token_responsible');
            $procedure = data_get($channel->settings, 'access_token_rotation_procedure');

            if (blank($expiresAt) || blank($responsible) || blank($procedure)) {
                $missingGovernance++;
                continue;
            }

            $expiration = Carbon::parse($expiresAt)->endOfDay();
            $nearestExpiration = $nearestExpiration === null || $expiration->lt($nearestExpiration)
                ? $expiration
                : $nearestExpiration;

            if ($expiration->isPast()) {
                $expired++;
            } elseif ($expiration->diffInDays(now()) <= $warningDays) {
                $expiring++;
            }
        }

        $status = $expired > 0 ? 'critical' : (($expiring + $missingGovernance) > 0 ? 'warning' : 'ok');
        $detail = $nearestExpiration
            ? 'Proxima expiracion: '.$nearestExpiration->format('Y-m-d').'.'
            : 'Hay tokens sin fecha/responsable/procedimiento.';

        return [
            'key' => 'token_rotation',
            'label' => 'Rotacion token WhatsApp',
            'status' => $status,
            'value' => "{$channels->count()} canales",
            'detail' => "{$detail} Vencidos: {$expired}. Por vencer: {$expiring}. Sin gobierno completo: {$missingGovernance}.",
            'action' => $status === 'ok'
                ? 'Tokens con responsable, fecha y procedimiento registrados.'
                : 'Actualizar fecha, responsable y procedimiento en Configuracion > Canales; rotar tokens vencidos.',
        ];
    }

    private function latestBackup(): array
    {
        $backupRoot = storage_path('app/backups');
        $directories = File::isDirectory($backupRoot)
            ? collect(File::directories($backupRoot))->filter(fn (string $path) => str_contains(basename($path), 'noiachat_'))
            : collect();

        if ($directories->isEmpty()) {
            return [
                'key' => 'backup',
                'label' => 'Backups locales',
                'status' => 'warning',
                'value' => 'Sin backup',
                'detail' => 'No hay backups locales en storage/app/backups.',
                'action' => 'Ejecutar noiachat:backup y validar sincronizacion externa.',
            ];
        }

        $latest = $directories->sortByDesc(fn (string $path) => File::lastModified($path))->first();
        $modifiedAt = Carbon::createFromTimestamp(File::lastModified($latest));
        $maxAge = (int) config('noiachat.health.max_backup_age_hours', 24);
        $ageHours = $modifiedAt->diffInHours(now());
        $status = $ageHours > $maxAge ? 'warning' : 'ok';

        return [
            'key' => 'backup',
            'label' => 'Backups locales',
            'status' => $status,
            'value' => basename($latest),
            'detail' => 'Ultimo backup: '.$modifiedAt->format('Y-m-d H:i:s').'.',
            'action' => $status === 'ok' ? 'Backup reciente disponible.' : 'Ejecutar backup y revisar cron.',
        ];
    }

    private function recentLogErrors(): array
    {
        $logPath = storage_path('logs/laravel.log');

        if (! File::exists($logPath)) {
            return [
                'key' => 'log_errors',
                'label' => 'Errores aplicacion',
                'status' => 'ok',
                'value' => 0,
                'detail' => 'No existe laravel.log.',
                'action' => 'Sin accion requerida.',
            ];
        }

        $content = File::get($logPath);
        $tail = strlen($content) > 200000 ? substr($content, -200000) : $content;
        $count = substr_count($tail, '.ERROR:') + substr_count($tail, 'production.ERROR') + substr_count($tail, 'local.ERROR');
        $max = (int) config('noiachat.health.max_recent_log_errors', 0);
        $status = $count > $max ? 'warning' : 'ok';

        return [
            'key' => 'log_errors',
            'label' => 'Errores aplicacion',
            'status' => $status,
            'value' => $count,
            'detail' => "{$count} errores encontrados en el tramo reciente de laravel.log.",
            'action' => $status === 'ok' ? 'Sin accion requerida.' : 'Revisar storage/logs/laravel.log y errores 500 recientes.',
        ];
    }

    private function overallStatus(Collection $checks): string
    {
        if ($checks->contains('status', 'critical')) {
            return 'critical';
        }

        return $checks->contains('status', 'warning') ? 'warning' : 'ok';
    }

    private function bytes(float|int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return round($bytes, 1).' '.$units[$index];
    }
}
