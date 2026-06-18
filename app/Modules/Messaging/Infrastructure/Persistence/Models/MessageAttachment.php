<?php

namespace App\Modules\Messaging\Infrastructure\Persistence\Models;

use App\Modules\Tenancy\Infrastructure\Persistence\Concerns\BelongsToDefaultTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageAttachment extends Model
{
    use BelongsToDefaultTenant;

    protected $fillable = ['company_id', 'branch_id', 'message_id', 'media_file_id'];

    public function mediaFile(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Media\Infrastructure\Persistence\Models\MediaFile::class);
    }
}
