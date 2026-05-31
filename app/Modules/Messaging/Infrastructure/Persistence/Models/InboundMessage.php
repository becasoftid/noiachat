<?php

namespace App\Modules\Messaging\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InboundMessage extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['contact_id', 'channel_id', 'conversation_id', 'provider_message_id', 'from_phone', 'body', 'payload'];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Contacts\Infrastructure\Persistence\Models\Contact::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Conversations\Infrastructure\Persistence\Models\Conversation::class);
    }

    public function optOutRequest(): HasOne
    {
        return $this->hasOne(\App\Modules\Webhooks\Infrastructure\Persistence\Models\OptOutRequest::class);
    }
}
