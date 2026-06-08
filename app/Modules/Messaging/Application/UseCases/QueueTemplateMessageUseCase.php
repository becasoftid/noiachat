<?php

namespace App\Modules\Messaging\Application\UseCases;

use App\Modules\Contacts\Infrastructure\Persistence\Models\Contact;
use App\Modules\Messaging\Domain\Enums\MessageType;
use App\Modules\Messaging\Infrastructure\Jobs\SendWhatsAppTemplateJob;
use App\Modules\Messaging\Infrastructure\Persistence\Models\Message;
use App\Modules\Messaging\Infrastructure\Persistence\Models\MessageTemplate;
use App\Modules\Shared\Domain\Exceptions\BusinessRuleException;
use Illuminate\Http\Request;

class QueueTemplateMessageUseCase
{
    public function __construct(
        private readonly QueueTextMessageUseCase $queueTextMessage,
    ) {}

    public function execute(
        Contact $contact,
        MessageTemplate $template,
        int $userId,
        array $variables = [],
        ?Request $request = null,
    ): Message {
        if (! $template->is_active || ! $template->currentVersion?->is_active) {
            throw new BusinessRuleException('La plantilla no está activa.');
        }

        $body = $this->renderBody($template->currentVersion->body, $variables);

        $message = $this->queueTextMessage->execute(
            $contact,
            $template->channel_id,
            $body,
            $userId,
            $request,
            MessageType::TEMPLATE->value,
            $template->id,
            [
                'template_id' => $template->id,
                'template_version_id' => $template->currentVersion->id,
                'template_variables' => $variables,
            ],
            dispatch: false,
            complianceTemplate: $template,
        );

        if ($message->status === 'queued') {
            SendWhatsAppTemplateJob::dispatch($message->id);
        }

        return $message->fresh(['contact', 'events']);
    }

    private function renderBody(string $body, array $variables): string
    {
        return preg_replace_callback('/\{\{\s*(\d+)\s*\}\}/', function (array $matches) use ($variables) {
            $index = (int) $matches[1] - 1;

            return (string) ($variables[$index] ?? $matches[0]);
        }, $body) ?? $body;
    }
}
