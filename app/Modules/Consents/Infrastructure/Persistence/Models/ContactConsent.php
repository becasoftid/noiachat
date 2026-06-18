<?php

namespace App\Modules\Consents\Infrastructure\Persistence\Models;

use App\Models\User;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Channel;
use App\Modules\Tenancy\Infrastructure\Persistence\Concerns\BelongsToDefaultTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactConsent extends Model
{
    use BelongsToDefaultTenant;

    protected $fillable = ['company_id', 'branch_id', 'contact_id', 'channel_id', 'status', 'source', 'granted_by_user_id', 'revoked_by_user_id', 'granted_at', 'revoked_at', 'expires_at', 'notes'];

    protected function casts(): array
    {
        return ['granted_at' => 'datetime', 'revoked_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Contacts\Infrastructure\Persistence\Models\Contact::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by_user_id');
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by_user_id');
    }
}
