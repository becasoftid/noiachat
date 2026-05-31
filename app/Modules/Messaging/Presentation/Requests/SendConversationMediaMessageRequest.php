<?php

namespace App\Modules\Messaging\Presentation\Requests;

use App\Modules\Messaging\Domain\Enums\MessageType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendConversationMediaMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('messages.send') ?? false;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in([MessageType::IMAGE->value, MessageType::DOCUMENT->value])],
            'body' => ['nullable', 'string', 'max:1024'],
            'file' => ['required', 'file', 'max:5120'],
        ];
    }
}
