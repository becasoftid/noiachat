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
}
