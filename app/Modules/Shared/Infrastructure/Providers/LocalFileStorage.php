<?php

namespace App\Modules\Shared\Infrastructure\Providers;

use App\Modules\Shared\Domain\Contracts\FileStorageInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class LocalFileStorage implements FileStorageInterface
{
    public function store(UploadedFile $file, string $directory): array
    {
        $path = $file->store($directory, 'public');

        return [
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
            'extension' => $file->getClientOriginalExtension(),
            'url' => Storage::disk('public')->url($path),
        ];
    }
}
