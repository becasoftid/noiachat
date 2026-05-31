<?php

namespace App\Modules\Shared\Domain\Contracts;

use Illuminate\Http\UploadedFile;

interface FileStorageInterface
{
    public function store(UploadedFile $file, string $directory): array;
}
