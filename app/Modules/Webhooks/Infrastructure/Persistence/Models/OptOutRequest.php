<?php

namespace App\Modules\Webhooks\Infrastructure\Persistence\Models;

use App\Modules\Tenancy\Infrastructure\Persistence\Concerns\BelongsToDefaultTenant;
use Illuminate\Database\Eloquent\Model;

class OptOutRequest extends Model
{
    use BelongsToDefaultTenant;

    protected $fillable = ['company_id', 'branch_id', 'inbound_message_id', 'contact_id', 'channel_id', 'keyword', 'requested_at'];

    protected function casts(): array
    {
        return ['requested_at' => 'datetime'];
    }
}
