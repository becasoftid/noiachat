<?php

namespace App\Modules\Media\Application\Services;

use App\Modules\Media\Infrastructure\Persistence\Models\MediaFile;
use App\Modules\Shared\Domain\Contracts\FileStorageInterface;
use App\Modules\Shared\Domain\Exceptions\BusinessRuleException;
use Illuminate\Http\UploadedFile;

class MediaService
{
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    public function __construct(private readonly FileStorageInterface $storage) {}

    public function upload(UploadedFile $file, ?int $userId = null): MediaFile
    {
        if (($file->getSize() ?? 0) === 0) {
            throw new BusinessRuleException('Empty files are not allowed.');
        }

        if (! in_array($file->getClientMimeType(), self::ALLOWED_MIME_TYPES, true)) {
            throw new BusinessRuleException('Unsupported file type.');
        }

        return MediaFile::create([
            ...$this->storage->store($file, 'messages'),
            'uploaded_by_user_id' => $userId,
        ]);
    }
}
