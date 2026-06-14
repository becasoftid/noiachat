<?php

namespace App\Modules\Media\Application\Services;

use App\Modules\Media\Infrastructure\Persistence\Models\MediaFile;
use App\Modules\Shared\Domain\Exceptions\BusinessRuleException;
use Illuminate\Support\Facades\Storage;

class PublicMediaUrlResolver
{
    public function resolve(MediaFile $media): string
    {
        $url = $this->buildUrl($media);

        if (! $this->isPublicHttpsUrl($url)) {
            throw new BusinessRuleException('El archivo multimedia debe estar disponible en una URL publica HTTPS para WhatsApp.');
        }

        return $url;
    }

    private function buildUrl(MediaFile $media): string
    {
        $diskUrl = config("filesystems.disks.{$media->disk}.url");

        if (is_string($diskUrl) && $diskUrl !== '') {
            return rtrim($diskUrl, '/').'/'.ltrim($media->path, '/');
        }

        return Storage::disk($media->disk)->url($media->path);
    }

    private function isPublicHttpsUrl(string $url): bool
    {
        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));

        return ($parts['scheme'] ?? null) === 'https'
            && $host !== ''
            && ! in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            && ! str_ends_with($host, '.local');
    }
}
