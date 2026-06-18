<?php

namespace App\Modules\Conversations\Presentation\Requests;

use App\Modules\Conversations\Infrastructure\Persistence\Models\Conversation;
use App\Modules\Messaging\Infrastructure\Persistence\Models\MessageTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReplyConversationTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('messages.send') ?? false;
    }

    public function rules(): array
    {
        return [
            'message_template_id' => ['required', 'integer', 'exists:message_templates,id'],
            'variables' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $conversation = $this->route('conversation');

            if (! $conversation instanceof Conversation) {
                return;
            }

            $template = MessageTemplate::query()
                ->forTenantContext()
                ->with('currentVersion')
                ->where('channel_id', $conversation->channel_id)
                ->find((int) $this->integer('message_template_id'));

            if (! $template || ! $template->currentVersion) {
                return;
            }

            $variables = $this->parseVariables((string) $this->input('variables', ''));
            $expected = $this->expectedVariableCount($template);

            if ($expected === 0 && $variables !== []) {
                $validator->errors()->add('variables', 'Esta plantilla no requiere variables.');

                return;
            }

            if ($expected > 0 && count($variables) !== $expected) {
                $validator->errors()->add('variables', "Esta plantilla requiere {$expected} variables.");
            }
        });
    }

    public function parsedVariables(): array
    {
        return $this->parseVariables((string) $this->input('variables', ''));
    }

    private function parseVariables(string $variables): array
    {
        if (trim($variables) === '') {
            return [];
        }

        return collect(explode('|', $variables))
            ->map(fn ($value) => trim($value))
            ->filter(fn ($value) => $value !== '')
            ->values()
            ->all();
    }

    private function expectedVariableCount(MessageTemplate $template): int
    {
        return $template->currentVersion?->expectedVariableCount() ?? 0;
    }
}
