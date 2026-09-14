<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ScholarPortalDocumentController extends Controller
{
    public function preview(Request $request, int|string $document)
    {
        return $this->proxy($document, inline: true);
    }

    public function download(Request $request, int|string $document)
    {
        return $this->proxy($document, inline: false);
    }

    private function proxy(int|string $document, bool $inline)
    {
        abort_unless(auth()->check(), 403);

        $baseUrl = rtrim((string) config('services.scholar_portal.api_base_url'), '/');
        $apiKey = (string) config('services.scholar_portal.file_api_key');

        abort_if($baseUrl === '' || $apiKey === '', 404);

        $portalResponse = Http::withHeaders([
            'X-SIMS-API-Key' => $apiKey,
            'Accept' => '*/*',
        ])
            ->timeout(30)
            ->get($baseUrl.'/api/sims/documents/'.$document);

        abort_unless($portalResponse->successful(), $portalResponse->status());

        $contentType = $portalResponse->header('Content-Type') ?: 'application/octet-stream';
        $filename = $this->filename($portalResponse->header('Content-Disposition'), $document);
        $disposition = $inline ? 'inline' : 'attachment';

        return response($portalResponse->body(), 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function filename(?string $contentDisposition, int|string $document): string
    {
        if ($contentDisposition && preg_match('/filename\*?=(?:UTF-8\'\')?"?([^";]+)"?/i', $contentDisposition, $matches)) {
            return basename(urldecode($matches[1]));
        }

        return 'document-'.$document;
    }
}
