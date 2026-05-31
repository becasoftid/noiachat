<?php

namespace App\Modules\Contacts\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('contacts.manage') ?? false;
    }

    public function rules(): array
    {
        return ['first_name' => ['required', 'string', 'max:120'], 'last_name' => ['nullable', 'string', 'max:120'], 'email' => ['nullable', 'email', 'max:255'], 'primary_phone' => ['required', 'string', 'max:30'], 'status' => ['nullable', 'in:active,blocked,no_contact,invalid']];
    }
}
