<?php

namespace App\Modules\Messaging\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasUuids, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['contact_id', 'channel_id', 'conversation_id', 'message_template_id', 'user_id', 'type', 'status', 'provider_message_id', 'body', 'retry_count', 'queued_at', 'sent_at', 'delivered_at', 'read_at', 'failed_at', 'meta'];

    protected function casts(): array
    {
        return [
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
            'failed_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Contacts\Infrastructure\Persistence\Models\Contact::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Conversations\Infrastructure\Persistence\Models\Conversation::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class, 'message_template_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(MessageEvent::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class);
    }

    public function providerLogs(): HasMany
    {
        return $this->hasMany(ProviderLog::class);
    }
}
