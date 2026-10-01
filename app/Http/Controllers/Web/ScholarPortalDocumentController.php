<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ScholarTerm;
use App\Models\StudentDocument;
use App\Support\SystemPermissions;
use Illuminate\Http\Client\ConnectionException;
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

        $metadata = StudentDocument::findOrFail($document);
        $permissions = app(SystemPermissions::class);
        $user = auth()->user();
        $permission = match (strtoupper($metadata->document_type)) {
            'COR', 'COG' => 'grade-submissions.view',
            default => 'scholars.view',
        };
        abort_unless($permissions->can($user, $permission), 403);
        $term = ScholarTerm::findOrFail($metadata->term_record_id);
        if ($permissions->shouldScopeToRegion($user)) {
            abort_unless($term->scholar()->whereHas('schoolInfo.campus.address',
                fn ($address) => $address->where('region_code', $permissions->regionCodeFor($user))
            )->exists(), 403);
        }

        $baseUrl = rtrim((string) config('services.scholar_portal.api_base_url'), '/');
        $apiKey = (string) config('services.scholar_portal.file_api_key');

        if ($baseUrl === '' || $apiKey === '') {
            Log::warning('Scholar Portal document proxy is missing configuration.', [
                'document' => $document,
                'has_base_url' => $baseUrl !== '',
                'has_api_key' => $apiKey !== '',
            ]);

            return response('Document retrieval is not configured. Please contact your administrator.', 503);
        }

        try {
            $portalResponse = Http::withHeaders([
                'X-SIMS-API-Key' => $apiKey,
                'Accept' => '*/*',
            ])
                ->connectTimeout(10)
                ->timeout(30)
                ->get($baseUrl.'/api/sims/documents/'.$document);
        } catch (ConnectionException $exception) {
            Log::warning('Scholar Portal document connection failed.', ['document' => $document]);
            return response('The Scholar Portal is unavailable. Please try again later.', 502);
        }

        if ($portalResponse->status() === 404) {
            return response('The uploaded document could not be found.', 404);
        }

        if (! $portalResponse->successful()) {
            Log::warning('Scholar Portal document proxy request failed.', [
                'document' => $document,
                'mode' => $inline ? 'preview' : 'download',
                'portal_url' => $baseUrl.'/api/sims/documents/'.$document,
                'portal_status' => $portalResponse->status(),
                'portal_content_type' => $portalResponse->header('Content-Type'),
            ]);

            return response('Unable to retrieve the document from the Scholar Portal.', 502);
        }

        $contentType = $portalResponse->header('Content-Type') ?: 'application/octet-stream';
        $filename = str_replace(["\r", "\n", '"', '\\'], '_',
            $metadata->file_name ?: $this->filename($portalResponse->header('Content-Disposition'), $document));
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
