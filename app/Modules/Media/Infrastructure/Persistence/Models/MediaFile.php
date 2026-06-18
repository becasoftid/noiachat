<?php

namespace App\Modules\Media\Infrastructure\Persistence\Models;

use App\Modules\Tenancy\Infrastructure\Persistence\Concerns\BelongsToDefaultTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MediaFile extends Model
{
    use BelongsToDefaultTenant, HasUuids, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['company_id', 'branch_id', 'disk', 'path', 'original_name', 'mime_type', 'size_bytes', 'extension', 'uploaded_by_user_id', 'meta'];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(\App\Modules\Messaging\Infrastructure\Persistence\Models\MessageAttachment::class);
    }
}
