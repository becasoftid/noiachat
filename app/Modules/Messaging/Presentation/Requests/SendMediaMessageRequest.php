<?php

namespace App\Modules\Messaging\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMediaMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('messages.send') ?? false;
    }

    public function rules(): array
    {
        return ['contact_id' => ['required', 'uuid', 'exists:contacts,id'], 'channel_id' => ['required', 'integer', 'exists:channels,id'], 'body' => ['nullable', 'string', 'max:1024'], 'file' => ['required', 'file', 'max:5120']];
    }
}
