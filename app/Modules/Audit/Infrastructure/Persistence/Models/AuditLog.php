<?php

namespace App\Modules\Audit\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = ['user_id', 'action', 'module', 'target_type', 'target_id', 'ip_address', 'user_agent', 'old_values_json', 'new_values_json'];

    protected function casts(): array
    {
        return ['old_values_json' => 'array', 'new_values_json' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
