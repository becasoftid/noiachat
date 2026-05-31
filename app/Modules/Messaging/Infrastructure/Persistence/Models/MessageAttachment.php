<?php

namespace App\Modules\Messaging\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageAttachment extends Model
{
    protected $fillable = ['message_id', 'media_file_id'];

    public function mediaFile(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Media\Infrastructure\Persistence\Models\MediaFile::class);
    }
}
