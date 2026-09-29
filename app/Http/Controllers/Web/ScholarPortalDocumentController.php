<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
        if (! auth()->check()) {
            Log::warning('Scholar Portal document proxy rejected unauthenticated request.', [
                'document' => $document,
                'mode' => $inline ? 'preview' : 'download',
                'url' => request()->fullUrl(),
            ]);

            abort(403);
        }

        $baseUrl = rtrim((string) config('services.scholar_portal.api_base_url'), '/');
        $apiKey = (string) config('services.scholar_portal.file_api_key');

        if ($baseUrl === '' || $apiKey === '') {
            Log::warning('Scholar Portal document proxy is missing configuration.', [
                'document' => $document,
                'has_base_url' => $baseUrl !== '',
                'has_api_key' => $apiKey !== '',
            ]);

            abort(404);
        }

        $portalResponse = Http::withHeaders([
            'X-SIMS-API-Key' => $apiKey,
            'Accept' => '*/*',
        ])
            ->timeout(30)
            ->get($baseUrl.'/api/sims/documents/'.$document);

        if (! $portalResponse->successful()) {
            Log::warning('Scholar Portal document proxy request failed.', [
                'document' => $document,
                'mode' => $inline ? 'preview' : 'download',
                'portal_url' => $baseUrl.'/api/sims/documents/'.$document,
                'portal_status' => $portalResponse->status(),
                'portal_content_type' => $portalResponse->header('Content-Type'),
                'portal_body_preview' => substr($portalResponse->body(), 0, 500),
            ]);

            return response('Unable to retrieve the document from the Scholar Portal.', 502);
        }

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
