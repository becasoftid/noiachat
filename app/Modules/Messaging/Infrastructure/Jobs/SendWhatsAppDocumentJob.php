<?php

namespace App\Modules\Messaging\Infrastructure\Jobs;

use App\Modules\Media\Application\Services\PublicMediaUrlResolver;
use App\Modules\Messaging\Application\DTOs\SendDocumentMessageDTO;
use App\Modules\Messaging\Application\Services\MessageStatusService;
use App\Modules\Messaging\Domain\Enums\MessageStatus;
use App\Modules\Messaging\Infrastructure\Persistence\Models\Message;
use App\Modules\Shared\Domain\Contracts\MessagingProviderInterface;
use App\Modules\Shared\Domain\Exceptions\BusinessRuleException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendWhatsAppDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly string $messageId) {}

    public function handle(MessagingProviderInterface $provider, MessageStatusService $statusService, PublicMediaUrlResolver $urlResolver): void
    {
        $message = Message::with('attachments.mediaFile')->findOrFail($this->messageId);
        $media = $message->attachments->first()?->mediaFile;
        $statusService->transition($message, MessageStatus::SENDING, ['job' => self::class], 'sending');

        if (! $media) {
            $payload = ['error' => 'El mensaje no tiene archivo multimedia adjunto.'];
            $message->providerLogs()->create(['provider' => 'whatsapp_cloud', 'direction' => 'outbound', 'event_type' => 'send_document_failed', 'payload' => $payload]);
            $statusService->transition($message->fresh(), MessageStatus::FAILED, $payload, 'media_missing');

            return;
        }

        try {
            $mediaUrl = $urlResolver->resolve($media);
        } catch (BusinessRuleException $exception) {
            $payload = ['error' => $exception->getMessage(), 'media_file_id' => $media?->id, 'path' => $media?->path];
            $message->providerLogs()->create(['provider' => 'whatsapp_cloud', 'direction' => 'outbound', 'event_type' => 'send_document_failed', 'payload' => $payload]);
            $statusService->transition($message->fresh(), MessageStatus::FAILED, $payload, 'media_url_invalid');

            return;
        }

        $response = $provider->sendDocument(new SendDocumentMessageDTO($message->id, $message->contact->primary_phone, $mediaUrl, $media->original_name, $message->body));
        $providerId = data_get($response, 'messages.0.id');
        $message->providerLogs()->create(['provider' => 'whatsapp_cloud', 'direction' => 'outbound', 'event_type' => 'send_document', 'external_event_id' => $providerId, 'payload' => $response]);
        if (data_get($response, 'error')) {
            $statusService->transition($message->fresh(), MessageStatus::FAILED, $response, 'provider_failed');

            return;
        }

        $message->update(['provider_message_id' => $providerId]);
        $statusService->transition($message->fresh(), MessageStatus::SENT, $response, 'provider_sent');
    }

    public function failed(Throwable $exception): void
    {
        if ($message = Message::find($this->messageId)) {
            $message->increment('retry_count');
            app(MessageStatusService::class)->transition($message, MessageStatus::FAILED, ['error' => $exception->getMessage()], 'job_failed');
        }
    }
}
