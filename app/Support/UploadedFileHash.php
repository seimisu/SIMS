<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

class UploadedFileHash
{
    public static function uploadedFile(UploadedFile $file): string
    {
        return hash_file('sha256', $file->getRealPath());
    }
}
