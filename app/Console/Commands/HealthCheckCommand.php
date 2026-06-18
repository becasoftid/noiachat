<?php

namespace App\Console\Commands;

use App\Modules\Reports\Application\Services\HealthMonitorService;
use Illuminate\Console\Command;

class HealthCheckCommand extends Command
{
    protected $signature = 'noiachat:health-check';

    protected $description = 'Valida salud operativa de cola, disco, webhook, backups y logs.';

    public function handle(HealthMonitorService $health): int
    {
        $summary = $health->summary();

        $this->line('Estado general: '.$summary['status']);
        $this->newLine();

        foreach ($summary['checks'] as $check) {
            $line = "[{$check['status']}] {$check['label']}: {$check['value']} - {$check['detail']}";

            match ($check['status']) {
                'critical' => $this->error($line),
                'warning' => $this->warn($line),
                default => $this->info($line),
            };

            $this->line('  Accion: '.$check['action']);
        }

        return $summary['status'] === 'critical' ? self::FAILURE : self::SUCCESS;
    }
}
