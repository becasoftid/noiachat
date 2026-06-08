<?php

namespace App\Modules\Messaging\Application\UseCases;

use App\Modules\Audit\Domain\Enums\AuditActionType;
use App\Modules\Compliance\Application\Services\ComplianceDecisionService;
use App\Modules\Compliance\Domain\Enums\EligibilityStatus;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Contact;
use App\Modules\Conversations\Application\Services\ConversationService;
use App\Modules\Messaging\Domain\Repositories\MessageRepositoryInterface;
use App\Modules\Messaging\Domain\Enums\MessageStatus;
use App\Modules\Messaging\Domain\Enums\MessageType;
use App\Modules\Messaging\Infrastructure\Jobs\SendWhatsAppTextJob;
use App\Modules\Messaging\Infrastructure\Persistence\Models\Message;
use App\Modules\Messaging\Infrastructure\Persistence\Models\MessageTemplate;
use App\Modules\Shared\Application\Services\AuditLogger;
use Illuminate\Http\Request;

class QueueTextMessageUseCase
{
    public function __construct(
        private readonly ComplianceDecisionService $compliance,
        private readonly ConversationService $conversations,
        private readonly AuditLogger $auditLogger,
        private readonly MessageRepositoryInterface $messages,
    ) {}

    public function execute(
        Contact $contact,
        int $channelId,
        string $body,
        int $userId,
        ?Request $request = null,
        string $type = MessageType::TEXT->value,
        ?int $messageTemplateId = null,
        array $meta = [],
        bool $dispatch = true,
        ?MessageTemplate $complianceTemplate = null,
    ): Message
    {
        $decision = $this->compliance->decide($contact, $channelId, $complianceTemplate);
        $conversation = $this->conversations->findOrCreate($contact->id, $channelId);

        $message = $this->messages->create([
            'contact_id' => $contact->id,
            'channel_id' => $channelId,
            'conversation_id' => $conversation->id,
            'user_id' => $userId,
            'message_template_id' => $messageTemplateId,
            'type' => $type,
            'status' => $decision === EligibilityStatus::ALLOWED ? MessageStatus::QUEUED->value : MessageStatus::BLOCKED_BY_POLICY->value,
            'body' => $body,
            'queued_at' => now(),
            'meta' => array_merge(['eligibility_status' => $decision->value], $meta),
        ]);

        $message->events()->create(['status' => $message->status, 'event_type' => 'message_created', 'payload' => ['eligibility_status' => $decision->value], 'occurred_at' => now()]);
        $this->auditLogger->log($userId, AuditActionType::SEND->value, 'messages', Message::class, $message->id, null, $message->toArray(), $request);

        if ($decision === EligibilityStatus::ALLOWED && $dispatch) {
            SendWhatsAppTextJob::dispatch($message->id);
        }

        return $message;
    }
}
