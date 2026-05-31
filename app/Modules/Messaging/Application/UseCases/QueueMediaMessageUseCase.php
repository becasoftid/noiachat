<?php

namespace App\Modules\Messaging\Application\UseCases;

use App\Modules\Contacts\Infrastructure\Persistence\Models\Contact;
use App\Modules\Media\Application\Services\MediaService;
use App\Modules\Messaging\Domain\Enums\MessageStatus;
use App\Modules\Messaging\Domain\Enums\MessageType;
use App\Modules\Messaging\Infrastructure\Jobs\SendWhatsAppDocumentJob;
use App\Modules\Messaging\Infrastructure\Jobs\SendWhatsAppImageJob;
use App\Modules\Messaging\Infrastructure\Persistence\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class QueueMediaMessageUseCase
{
    public function __construct(
        private readonly QueueTextMessageUseCase $queueTextMessage,
        private readonly MediaService $mediaService,
    ) {}

    public function execute(
        Contact $contact,
        int $channelId,
        string $type,
        UploadedFile $file,
        ?string $body,
        int $userId,
        ?Request $request = null,
    ): Message {
        $message = $this->queueTextMessage->execute($contact, $channelId, $body ?? '', $userId, $request);
        $message->update([
            'type' => $type,
            'status' => MessageStatus::QUEUED->value,
        ]);

        $media = $this->mediaService->upload($file, $userId);
        $message->attachments()->create(['media_file_id' => $media->id]);
        $message->events()->create([
            'status' => $message->status,
            'event_type' => 'attachment_added',
            'payload' => [
                'media_file_id' => $media->id,
                'message_type' => $type,
                'mime_type' => $media->mime_type,
            ],
            'occurred_at' => now(),
        ]);

        if ($type === MessageType::IMAGE->value) {
            SendWhatsAppImageJob::dispatch($message->id);
        } else {
            SendWhatsAppDocumentJob::dispatch($message->id);
        }

        return $message->fresh(['attachments.mediaFile', 'events']);
    }
}
