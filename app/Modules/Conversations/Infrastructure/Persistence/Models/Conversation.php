<?php

namespace App\Modules\Conversations\Infrastructure\Persistence\Models;

use App\Models\User;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Channel;
use App\Modules\Tenancy\Infrastructure\Persistence\Concerns\BelongsToDefaultTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use BelongsToDefaultTenant, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['company_id', 'branch_id', 'contact_id', 'channel_id', 'assigned_user_id', 'status', 'last_message_at', 'last_read_at'];

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

    public function inboundMessages(): HasMany
    {
        return $this->hasMany(\App\Modules\Messaging\Infrastructure\Persistence\Models\InboundMessage::class);
    }
}
