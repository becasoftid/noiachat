<?php

namespace App\Modules\Messaging\Infrastructure\Jobs;

use App\Modules\Messaging\Application\DTOs\SendTextMessageDTO;
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

class SendWhatsAppTextJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly string $messageId) {}

    public function handle(MessagingProviderInterface $provider, MessageStatusService $statusService): void
    {
        $message = Message::findOrFail($this->messageId);
        $statusService->transition($message, MessageStatus::SENDING, ['job' => self::class], 'sending');
        $response = $provider->sendText(new SendTextMessageDTO($message->id, $message->contact->primary_phone, (string) $message->body));
        $providerId = data_get($response, 'messages.0.id');

        $message->providerLogs()->create(['provider' => 'whatsapp_cloud', 'direction' => 'outbound', 'event_type' => 'send_text', 'external_event_id' => $providerId, 'payload' => $response]);
        $message->update(['provider_message_id' => $providerId]);
        $statusService->transition($message->fresh(), MessageStatus::SENT, $response, 'provider_sent');
    }

    public function failed(Throwable $exception): void
    {
        if ($message = Message::find($this->messageId)) {
            $message->increment('retry_count');
            $message->providerLogs()->create(['provider' => 'whatsapp_cloud', 'direction' => 'outbound', 'event_type' => 'send_text_failed', 'payload' => ['error' => $exception->getMessage()]]);
            app(MessageStatusService::class)->transition($message, MessageStatus::FAILED, ['error' => $exception->getMessage()], 'job_failed');
        }
    }
}
