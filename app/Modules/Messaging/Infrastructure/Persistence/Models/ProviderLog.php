<?php

namespace App\Modules\Messaging\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderLog extends Model
{
    protected $fillable = ['provider', 'direction', 'event_type', 'external_event_id', 'message_id', 'inbound_message_id', 'payload'];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }

    public function hasError(): bool
    {
        return filled($this->errorMessage())
            || filled($this->errorCode())
            || str_contains((string) $this->event_type, 'failed');
    }

    public function errorMessage(): ?string
    {
        $error = data_get($this->payload, 'error');

        if (is_string($error)) {
            return $error;
        }

        return data_get($this->payload, 'error.message')
            ?? data_get($this->payload, 'error.error_user_msg')
            ?? data_get($this->payload, 'message');
    }

    public function errorCode(): ?string
    {
        $code = data_get($this->payload, 'error.code')
            ?? data_get($this->payload, 'code');

        return filled($code) ? (string) $code : null;
    }

    public function errorDetails(): ?string
    {
        return data_get($this->payload, 'error.error_data.details')
            ?? data_get($this->payload, 'error.details')
            ?? data_get($this->payload, 'details');
    }
}
