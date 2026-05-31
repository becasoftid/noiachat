<?php

namespace App\Modules\Contacts\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBlacklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('contacts.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'channel_id' => ['required', 'integer', 'exists:channels,id'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
