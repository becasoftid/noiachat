<?php

namespace App\Modules\Settings\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('platform.access') ?? false;
    }

    public function rules(): array
    {
        return [
            'channel_id' => ['required', 'integer', 'exists:channels,id'],
            'name' => ['required', 'string', 'max:120'],
            'external_template_id' => ['nullable', 'string', 'max:120'],
            'language' => ['required', 'string', 'max:10'],
            'body' => ['required', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
