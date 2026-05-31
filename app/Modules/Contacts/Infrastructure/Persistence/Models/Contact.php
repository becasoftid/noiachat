<?php

namespace App\Modules\Contacts\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use HasUuids, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['first_name', 'last_name', 'full_name', 'email', 'primary_phone', 'status', 'meta'];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    public function contactChannels(): HasMany
    {
        return $this->hasMany(ContactChannel::class);
    }

    public function contactConsents(): HasMany
    {
        return $this->hasMany(\App\Modules\Consents\Infrastructure\Persistence\Models\ContactConsent::class);
    }

    public function contactBlacklist(): HasMany
    {
        return $this->hasMany(\App\Modules\Consents\Infrastructure\Persistence\Models\ContactBlacklist::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(\App\Modules\Messaging\Infrastructure\Persistence\Models\Message::class);
    }

    public function inboundMessages(): HasMany
    {
        return $this->hasMany(\App\Modules\Messaging\Infrastructure\Persistence\Models\InboundMessage::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(\App\Modules\Conversations\Infrastructure\Persistence\Models\Conversation::class);
    }
}
