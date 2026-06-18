<?php

namespace App\Modules\Messaging\Application\Services;

use App\Modules\Messaging\Domain\Enums\MessageStatus;
use App\Modules\Messaging\Infrastructure\Persistence\Models\Message;

class MessageStatusService
{
    public function transition(Message $message, MessageStatus $status, ?array $payload = null, ?string $eventType = null): Message
    {
        $attributes = ['status' => $status->value];

        if ($status === MessageStatus::SENT) {
            $attributes['sent_at'] = now();
        }

        if ($status === MessageStatus::DELIVERED) {
            $attributes['delivered_at'] = now();
        }

        if ($status === MessageStatus::READ) {
            $attributes['read_at'] = now();
        }

        if (in_array($status, [MessageStatus::FAILED, MessageStatus::BOUNCED], true)) {
            $attributes['failed_at'] = now();
        }

        $message->update($attributes);
        $message->events()->create([
            ...$message->tenantAttributes(),
            'status' => $status->value,
            'event_type' => $eventType,
            'payload' => $payload,
            'occurred_at' => now(),
        ]);

        return $message->fresh();
    }
}
