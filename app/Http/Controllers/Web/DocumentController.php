<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\ListPrograms;
use App\Models\ListReferences;
use App\Models\LocationRegions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $categorySearch = $request->input('categorySearch');

        return Inertia::render('Web/documentPage', [
            'documents' => Document::with(['category:id,name', 'targets'])
                ->when($search, function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('title', 'ILIKE', "%{$search}%")
                            ->orWhere('description', 'ILIKE', "%{$search}%")
                            ->orWhere('original_filename', 'ILIKE', "%{$search}%");
                    });
                })
                ->latest()
                ->paginate(10)
                ->withQueryString(),
            'categories' => DocumentCategory::orderBy('sort_order')
                ->orderBy('name')
                ->when($categorySearch, function ($query, $categorySearch) {
                    $query->where(function ($q) use ($categorySearch) {
                        $q->where('name', 'ILIKE', "%{$categorySearch}%")
                            ->orWhere('description', 'ILIKE', "%{$categorySearch}%");
                    });
                })
                ->paginate(10)
                ->withQueryString(),
            'categoryOptions' => DocumentCategory::where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name']),
            'regionOptions' => LocationRegions::where('is_active', true)
                ->orderBy('region')
                ->get()
                ->map(fn ($region) => [
                    'id' => $region->code,
                    'name' => $region->region ?? $region->name,
                ])
                ->values(),
            'scholarshipOptions' => ListReferences::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'programOptions' => ListPrograms::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateDocument($request);
        $file = $request->file('file');
        $path = $file->store('documents', 'public');

        $document = Document::create([
            'document_category_id' => $data['category_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'is_active' => $request->boolean('is_active'),
            'published_at' => $request->boolean('publish_now') ? now() : null,
            'uploaded_by' => Auth::id(),
        ]);

        $this->syncTargets($document, $data['targets'] ?? []);

        return back()->with('flash', [
            'status' => 'success',
            'title' => 'Document Uploaded',
            'message' => 'The document is now available based on its selected audience.',
        ]);
    }

    public function update(Request $request, Document $document)
    {
        $data = $this->validateDocument($request, $document);

        $payload = [
            'document_category_id' => $data['category_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'published_at' => $request->boolean('publish_now')
                ? ($document->published_at ?? now())
                : null,
            'updated_by' => Auth::id(),
        ];

        if ($request->hasFile('file')) {
            Storage::disk('public')->delete($document->file_path);
            $file = $request->file('file');
            $payload = array_merge($payload, [
                'file_path' => $file->store('documents', 'public'),
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ]);
        }

        $document->update($payload);
        $this->syncTargets($document, $data['targets'] ?? []);

        return back()->with('flash', [
            'status' => 'success',
            'title' => 'Document Updated',
            'message' => 'The document details and availability were updated.',
        ]);
    }

    public function destroy(Document $document)
    {
        $document->delete();

        return back()->with('flash', [
            'status' => 'success',
            'title' => 'Document Deleted',
            'message' => 'The document was removed from the list.',
        ]);
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        DocumentCategory::create([
            ...$data,
            'sort_order' => 0,
            'is_active' => $request->boolean('is_active'),
            'created_by' => Auth::id(),
        ]);

        return back()->with('flash', [
            'status' => 'success',
            'title' => 'Category Created',
            'message' => 'The document category has been added.',
        ]);
    }

    public function updateCategory(Request $request, DocumentCategory $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $category->update([
            ...$data,
            'is_active' => $request->boolean('is_active'),
            'updated_by' => Auth::id(),
        ]);

        return back()->with('flash', [
            'status' => 'success',
            'title' => 'Category Updated',
            'message' => 'The document category has been updated.',
        ]);
    }

    public function destroyCategory(DocumentCategory $category)
    {
        $category->delete();

        return back()->with('flash', [
            'status' => 'success',
            'title' => 'Category Deleted',
            'message' => 'The document category has been removed.',
        ]);
    }

    public function publicIndex(Request $request)
    {
        $documents = Document::with('category:id,name')
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(function ($query) use ($request) {
                $query->whereHas('targets', fn ($target) => $target->where('target_type', 'all'));

                foreach (['region', 'scholarship_program', 'program', 'school'] as $type) {
                    if ($request->filled($type)) {
                        $query->orWhereHas('targets', function ($target) use ($type, $request) {
                            $target->where('target_type', $type)
                                ->where('target_id', (string) $request->input($type));
                        });
                    }
                }
            })
            ->latest('published_at')
            ->get()
            ->map(fn ($document) => [
                'id' => $document->id,
                'title' => $document->title,
                'description' => $document->description,
                'category' => $document->category?->name,
                'original_filename' => $document->original_filename,
                'mime_type' => $document->mime_type,
                'file_size' => $document->file_size,
                'published_at' => $document->published_at?->toDateTimeString(),
                'download_url' => route('documents.download', $document),
            ]);

        return response()->json($documents);
    }

    public function download(Document $document)
    {
        abort_unless(Auth::check() || ($document->is_active && $document->published_at && $document->published_at->lte(now())), 404);
        abort_unless(Storage::disk('public')->exists($document->file_path), 404);

        return Storage::disk('public')->download($document->file_path, $document->original_filename);
    }

    private function validateDocument(Request $request, ?Document $document = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:document_categories,id'],
            'is_active' => ['boolean'],
            'publish_now' => ['boolean'],
            'file' => [$document ? 'nullable' : 'required', 'file', 'max:20480'],
            'targets' => ['required', 'array', 'min:1'],
            'targets.*.target_type' => ['required', Rule::in(['all', 'region', 'scholarship_program', 'program', 'school'])],
            'targets.*.target_id' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function syncTargets(Document $document, array $targets): void
    {
        $document->targets()->delete();

        collect($targets)
            ->filter(fn ($target) => ($target['target_type'] ?? null) === 'all' || filled($target['target_id'] ?? null))
            ->unique(fn ($target) => ($target['target_type'] ?? '').':'.($target['target_id'] ?? ''))
            ->each(fn ($target) => $document->targets()->create([
                'target_type' => $target['target_type'],
                'target_id' => $target['target_type'] === 'all' ? null : (string) $target['target_id'],
            ]));
    }
}
