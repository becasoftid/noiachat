<?php

namespace App\Modules\Consents\Infrastructure\Persistence\Models;

use App\Models\User;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Channel;
use App\Modules\Tenancy\Infrastructure\Persistence\Concerns\BelongsToDefaultTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactBlacklist extends Model
{
    use BelongsToDefaultTenant;

    public $timestamps = false;

    protected $table = 'contact_blacklist';

    protected $fillable = ['company_id', 'branch_id', 'contact_id', 'channel_id', 'reason', 'created_by_user_id', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Contacts\Infrastructure\Persistence\Models\Contact::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
