<?php

namespace App\Modules\Messaging\Infrastructure\Jobs;

use App\Modules\Messaging\Application\DTOs\SendImageMessageDTO;
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

class SendWhatsAppImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly string $messageId) {}

    public function handle(MessagingProviderInterface $provider, MessageStatusService $statusService): void
    {
        $message = Message::with('attachments.mediaFile')->findOrFail($this->messageId);
        $media = $message->attachments->first()?->mediaFile;
        $response = $provider->sendImage(new SendImageMessageDTO($message->id, $message->contact->primary_phone, asset('storage/'.$media->path), $message->body));
        $message->providerLogs()->create(['provider' => 'whatsapp_cloud', 'direction' => 'outbound', 'event_type' => 'send_image', 'external_event_id' => data_get($response, 'messages.0.id'), 'payload' => $response]);
        $statusService->transition($message, MessageStatus::SENT, $response, 'provider_sent');
    }

    public function failed(Throwable $exception): void
    {
        if ($message = Message::find($this->messageId)) {
            $message->increment('retry_count');
            app(MessageStatusService::class)->transition($message, MessageStatus::FAILED, ['error' => $exception->getMessage()], 'job_failed');
        }
    }
}
