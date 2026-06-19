<?php

namespace App\Modules\Tenancy\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommercialWhatsAppChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('whatsapp.integration.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'branch_id' => ['nullable', 'uuid'],
            'is_active' => ['nullable', 'boolean'],
            'settings.api_base_url' => ['nullable', 'url', 'starts_with:https://', 'max:255'],
            'settings.phone_number_id' => ['nullable', 'string', 'regex:/^[0-9]{6,30}$/'],
            'settings.business_account_id' => ['nullable', 'string', 'regex:/^[0-9]{6,30}$/'],
            'settings.access_token' => ['nullable', 'string', 'min:20', 'max:1000'],
            'settings.webhook_verify_token' => ['nullable', 'string', 'min:8', 'max:255'],
            'settings.app_secret' => ['nullable', 'string', 'min:16', 'max:255'],
            'settings.access_token_expires_at' => ['nullable', 'date'],
            'settings.access_token_rotated_at' => ['nullable', 'date'],
            'settings.access_token_responsible' => ['nullable', 'string', 'max:120'],
            'settings.access_token_rotation_procedure' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'settings.api_base_url.starts_with' => 'La URL base de Meta debe usar HTTPS.',
            'settings.phone_number_id.regex' => 'El Phone Number ID debe contener solo digitos.',
            'settings.business_account_id.regex' => 'El WhatsApp Business Account ID debe contener solo digitos.',
            'settings.access_token.min' => 'El access token parece demasiado corto.',
            'settings.app_secret.min' => 'El app secret parece demasiado corto.',
            'settings.webhook_verify_token.min' => 'El webhook verify token parece demasiado corto.',
            'settings.access_token_expires_at.date' => 'La fecha de expiracion del token no es valida.',
            'settings.access_token_rotated_at.date' => 'La fecha de ultima rotacion del token no es valida.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre del canal',
            'branch_id' => 'sede',
            'settings.api_base_url' => 'URL base de Meta',
            'settings.phone_number_id' => 'Phone Number ID',
            'settings.business_account_id' => 'WhatsApp Business Account ID',
            'settings.access_token' => 'access token',
            'settings.webhook_verify_token' => 'webhook verify token',
            'settings.app_secret' => 'app secret',
            'settings.access_token_expires_at' => 'fecha de expiracion del token',
            'settings.access_token_rotated_at' => 'fecha de ultima rotacion del token',
            'settings.access_token_responsible' => 'responsable del token',
            'settings.access_token_rotation_procedure' => 'procedimiento de rotacion',
        ];
    }
}
