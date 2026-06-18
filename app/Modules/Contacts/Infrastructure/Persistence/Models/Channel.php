<?php

namespace App\Modules\Contacts\Infrastructure\Persistence\Models;

use App\Modules\Consents\Infrastructure\Persistence\Models\ContactBlacklist;
use App\Modules\Consents\Infrastructure\Persistence\Models\ContactConsent;
use App\Modules\Conversations\Infrastructure\Persistence\Models\Conversation;
use App\Modules\Messaging\Infrastructure\Persistence\Models\InboundMessage;
use App\Modules\Messaging\Infrastructure\Persistence\Models\Message;
use App\Modules\Messaging\Infrastructure\Persistence\Models\MessageTemplate;
use App\Modules\Tenancy\Infrastructure\Persistence\Concerns\BelongsToDefaultTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Channel extends Model
{
    use BelongsToDefaultTenant;

    protected $fillable = ['company_id', 'branch_id', 'name', 'slug', 'is_active', 'settings'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'settings' => 'array'];
    }

    public function contactChannels(): HasMany
    {
        return $this->hasMany(ContactChannel::class);
    }

    public function consents(): HasMany
    {
        return $this->hasMany(ContactConsent::class);
    }

    public function blacklistEntries(): HasMany
    {
        return $this->hasMany(ContactBlacklist::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function inboundMessages(): HasMany
    {
        return $this->hasMany(InboundMessage::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(MessageTemplate::class);
    }
}
