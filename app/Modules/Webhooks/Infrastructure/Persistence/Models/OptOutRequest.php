<?php

namespace App\Modules\Webhooks\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class OptOutRequest extends Model
{
    protected $fillable = ['inbound_message_id', 'contact_id', 'channel_id', 'keyword', 'requested_at'];

    protected function casts(): array
    {
        return ['requested_at' => 'datetime'];
    }
}
