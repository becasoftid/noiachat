<?php

namespace App\Modules\Messaging\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RetryMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('messages.send') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
