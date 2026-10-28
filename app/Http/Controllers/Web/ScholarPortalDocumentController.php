<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ScholarTerm;
use App\Models\StudentDocument;
use App\Support\SystemPermissions;
use Illuminate\Http\Request;
use App\Support\ScholarPortalFiles;
use App\Models\StudentProfileRequest;
use App\Models\studentLandbankRequest;
use App\Models\Scholars;
use Illuminate\Support\Facades\Log;

class ScholarPortalDocumentController extends Controller
{
    public function preview(Request $request, int|string $document)
    {
        return $this->serve($document, inline: true);
    }

    public function download(Request $request, int|string $document)
    {
        return $this->serve($document, inline: false);
    }

    private function serve(int|string $document, bool $inline)
    {
        if (! auth()->check()) {
            Log::warning('Scholar Portal document request rejected unauthenticated request.', [
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
            abort_unless($permissions->regionCodeFor($user), 403);
            abort_unless($term->scholar()->whereHas('schoolInfo.campus.address',
                fn ($address) => $address->where('region_code', $permissions->regionCodeFor($user))
            )->exists(), 403);
        }

        return app(ScholarPortalFiles::class)->send($metadata->file_path, $metadata->file_name, $inline);
    }

    public function profileProof(Request $request, int|string $record, string $mode)
    {
        $this->requirePermission('profile-requests.view');
        $metadata = StudentProfileRequest::findOrFail($record);
        $this->authorizeScholar($metadata->scholar_id);

        return app(ScholarPortalFiles::class)->send($metadata->proof, inline: $mode === 'preview');
    }

    public function landbankProof(Request $request, int|string $record, string $mode)
    {
        $this->requirePermission('landbank-requests.view');
        $metadata = studentLandbankRequest::findOrFail($record);
        $this->authorizeScholar($metadata->scholar_id);

        return app(ScholarPortalFiles::class)->send($metadata->uploaded_file, inline: $mode === 'preview');
    }

    public function photo(Request $request, int|string $scholar)
    {
        abort_unless(auth()->check(), 403);
        $permissions = app(SystemPermissions::class);
        abort_unless(collect(['scholars.view', 'grade-submissions.view', 'profile-requests.view', 'landbank-requests.view'])
            ->contains(fn ($permission) => $permissions->can(auth()->user(), $permission)), 403);
        $record = $this->authorizeScholar($scholar);
        $path = $record->profile?->getRawOriginal('photo');
        abort_unless(is_string($path) && str_starts_with($path, 'profile-photos/'), 404);

        return app(ScholarPortalFiles::class)->send($path);
    }

    private function requirePermission(string $permission): void
    {
        abort_unless(auth()->check(), 403);
        abort_unless(app(SystemPermissions::class)->can(auth()->user(), $permission), 403);
    }

    private function authorizeScholar(int|string $id): Scholars
    {
        $permissions = app(SystemPermissions::class);
        $user = auth()->user();
        $query = Scholars::whereKey($id);
        if ($permissions->shouldScopeToRegion($user)) {
            $region = $permissions->regionCodeFor($user);
            abort_unless($region, 403);
            $query->whereHas('schoolInfo.campus.address', fn ($address) => $address->where('region_code', $region));
        }

        return $query->firstOrFail();
    }
}
