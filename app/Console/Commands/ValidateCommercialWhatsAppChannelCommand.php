<?php

namespace App\Console\Commands;

use App\Modules\Contacts\Infrastructure\Persistence\Models\Channel;
use App\Modules\Messaging\Application\Services\WhatsAppConnectionTestService;
use App\Modules\Messaging\Application\Services\WhatsAppTemplateSyncService;
use Illuminate\Console\Command;
use RuntimeException;

class ValidateCommercialWhatsAppChannelCommand extends Command
{
    protected $signature = 'noiachat:whatsapp-commercial-validate
        {channel_id : ID del canal WhatsApp a validar}
        {--sync-templates : Sincroniza plantillas despues de validar la conexion}';

    protected $description = 'Valida un canal WhatsApp comercial real contra Meta y opcionalmente sincroniza plantillas.';

    public function handle(WhatsAppConnectionTestService $tester, WhatsAppTemplateSyncService $syncService): int
    {
        $channel = Channel::query()
            ->whereKey($this->argument('channel_id'))
            ->where('slug', 'whatsapp')
            ->first();

        if (! $channel) {
            $this->error('No existe el canal WhatsApp indicado.');

            return self::FAILURE;
        }

        $this->info("Validando canal {$channel->id} · {$channel->name}");

        try {
            $result = $tester->test($channel);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $settings = $channel->settings ?? [];
        $settings['last_connection_test'] = $result;
        $channel->update(['settings' => $settings]);

        $this->info('Conexion con Meta validada.');
        $this->line('- Numero: '.($result['display_phone_number'] ?? $result['phone_number_id'] ?? 'sin dato'));
        $this->line('- Nombre verificado: '.($result['verified_name'] ?? 'sin dato'));
        $this->line('- WABA: '.($result['business_name'] ?? $result['business_account_id'] ?? 'sin dato'));

        if (! $this->option('sync-templates')) {
            return self::SUCCESS;
        }

        try {
            $sync = $syncService->sync($channel);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Plantillas sincronizadas: {$sync['synced']} sincronizadas, {$sync['created']} creadas, {$sync['updated']} actualizadas, {$sync['approved']} aprobadas, {$sync['skipped']} omitidas.");

        return self::SUCCESS;
    }
}
