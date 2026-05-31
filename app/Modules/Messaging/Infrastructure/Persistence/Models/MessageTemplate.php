<?php

namespace App\Modules\Messaging\Infrastructure\Persistence\Models;

use App\Modules\Contacts\Infrastructure\Persistence\Models\Channel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MessageTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = ['channel_id', 'name', 'external_template_id', 'is_active', 'current_version_id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(TemplateVersion::class);
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(TemplateVersion::class, 'current_version_id');
    }
}
