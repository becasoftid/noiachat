<?php

namespace App\Modules\Conversations\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('messages.send') ?? false;
    }

    public function rules(): array
    {
        return [
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['required', 'in:open,pending,resolved,closed'],
        ];
    }
}
