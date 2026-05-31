<?php

namespace App\Modules\Webhooks\Infrastructure\Jobs;

use App\Modules\Webhooks\Application\UseCases\ProcessWhatsAppWebhookUseCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessWhatsAppWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly array $payload) {}

    public function handle(ProcessWhatsAppWebhookUseCase $useCase): void
    {
        $useCase->execute($this->payload);
    }
}
