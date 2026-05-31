<?php

namespace App\Modules\Messaging\Infrastructure\Jobs;

use App\Modules\Messaging\Application\DTOs\SendTemplateMessageDTO;
use App\Modules\Messaging\Application\Services\MessageStatusService;
use App\Modules\Messaging\Domain\Enums\MessageStatus;
use App\Modules\Messaging\Infrastructure\Persistence\Models\Message;
use App\Modules\Shared\Domain\Contracts\MessagingProviderInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendWhatsAppTemplateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly string $messageId) {}

    public function handle(MessagingProviderInterface $provider, MessageStatusService $statusService): void
    {
        $message = Message::with(['contact', 'template.currentVersion'])->findOrFail($this->messageId);
        $template = $message->template;

        $statusService->transition($message, MessageStatus::SENDING, ['job' => self::class], 'sending');

        $response = $provider->sendTemplate(new SendTemplateMessageDTO(
            $message->id,
            $message->contact->primary_phone,
            (string) $template->external_template_id ?: $template->name,
            (string) $template->currentVersion?->language ?: 'es',
            data_get($message->meta, 'template_variables', []),
        ));

        $providerId = data_get($response, 'messages.0.id');
        $message->providerLogs()->create([
            'provider' => 'whatsapp_cloud',
            'direction' => 'outbound',
            'event_type' => 'send_template',
            'external_event_id' => $providerId,
            'payload' => $response,
        ]);
        $message->update(['provider_message_id' => $providerId]);
        $statusService->transition($message->fresh(), MessageStatus::SENT, $response, 'provider_sent');
    }

    public function failed(Throwable $exception): void
    {
        if ($message = Message::find($this->messageId)) {
            $message->increment('retry_count');
            $message->providerLogs()->create([
                'provider' => 'whatsapp_cloud',
                'direction' => 'outbound',
                'event_type' => 'send_template_failed',
                'payload' => ['error' => $exception->getMessage()],
            ]);
            app(MessageStatusService::class)->transition($message, MessageStatus::FAILED, ['error' => $exception->getMessage()], 'job_failed');
        }
    }
}
