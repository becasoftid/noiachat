<?php

namespace App\Modules\Conversations\Infrastructure\Persistence\Models;

use App\Models\User;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Channel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['contact_id', 'channel_id', 'assigned_user_id', 'status', 'last_message_at', 'last_read_at'];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'last_read_at' => 'datetime',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Contacts\Infrastructure\Persistence\Models\Contact::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(\App\Modules\Messaging\Infrastructure\Persistence\Models\Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(\App\Modules\Messaging\Infrastructure\Persistence\Models\Message::class)->latestOfMany('created_at');
    }

    public function inboundMessages(): HasMany
    {
        return $this->hasMany(\App\Modules\Messaging\Infrastructure\Persistence\Models\InboundMessage::class);
    }

    public function latestInboundMessage(): HasOne
    {
        return $this->hasOne(\App\Modules\Messaging\Infrastructure\Persistence\Models\InboundMessage::class)->latestOfMany('created_at');
    }
}
