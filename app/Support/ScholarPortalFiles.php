<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class ScholarPortalFiles
{
    public function send(?string $path, ?string $filename = null, bool $inline = true)
    {
        $relativePath = str_replace('\\', '/', (string) $path);
        // Older Portal records can contain upload URLs instead of relative paths.
        if (preg_match('#^https?://#i', $relativePath)) {
            $relativePath = parse_url($relativePath, PHP_URL_PATH) ?: '';
            abort_unless(preg_match('#^/(?:api/)?uploads/#i', $relativePath), 404);
        }
        $relativePath = preg_replace('#^/?(?:api/)?uploads/#i', '', $relativePath);
        abort_if($relativePath === '' || str_starts_with($relativePath, '/')
            || str_contains($relativePath, ':') || str_contains($relativePath, "\0")
            || preg_match('#(^|/)\.\.(/|$)#', $relativePath), 404);

        $disk = Storage::disk('scholar_portal');
        $root = realpath(config('filesystems.disks.scholar_portal.root'));
        $file = realpath($disk->path($relativePath));
        abort_unless($root !== false && $file !== false
            && str_starts_with($file, $root.DIRECTORY_SEPARATOR)
            && is_file($file) && is_readable($file), 404);

        $filename = basename(str_replace('\\', '/', $filename ?: $relativePath));
        $filename = str_replace(["\r", "\n", '"'], '_', $filename);
        $headers = [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ];

        return $inline
            ? $disk->response($relativePath, $filename, $headers)
            : $disk->download($relativePath, $filename, $headers);
    }
}
