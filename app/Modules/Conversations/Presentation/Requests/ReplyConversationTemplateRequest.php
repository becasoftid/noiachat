<?php

namespace App\Modules\Conversations\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
}
