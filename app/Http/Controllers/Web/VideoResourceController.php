<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ListPrograms;
use App\Models\ListReferences;
use App\Models\LocationRegions;
use App\Models\VideoResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class VideoResourceController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        return Inertia::render('Web/videoResourcePage', [
            'resources' => VideoResource::with('targets')
                ->when($search, function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('title', 'ILIKE', "%{$search}%")
                            ->orWhere('description', 'ILIKE', "%{$search}%")
                            ->orWhere('video_url', 'ILIKE', "%{$search}%");
                    });
                })
                ->latest()
                ->paginate(10)
                ->through(fn ($resource) => [
                    'id' => $resource->id,
                    'title' => $resource->title,
                    'description' => $resource->description,
                    'video_url' => $resource->video_url,
                    'thumbnail_url' => $this->thumbnailUrl($resource),
                    'is_active' => $resource->is_active,
                    'published_at' => $resource->published_at,
                    'targets' => $resource->targets,
                ])
                ->withQueryString(),
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
        $data = $this->validateResource($request);
        $thumbnailPath = $request->hasFile('thumbnail')
            ? $request->file('thumbnail')->store('video-thumbnails', 'public')
            : null;

        $resource = VideoResource::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'video_url' => $data['video_url'],
            'thumbnail_url' => $data['thumbnail_url'] ?? null,
            'thumbnail_path' => $thumbnailPath,
            'is_active' => $request->boolean('is_active'),
            'published_at' => $request->boolean('publish_now') ? now() : null,
            'created_by' => Auth::id(),
        ]);

        $this->syncTargets($resource, $data['targets'] ?? []);

        return back()->with('flash', [
            'status' => 'success',
            'title' => 'Video Resource Created',
            'message' => 'The video resource is now available based on its selected audience.',
        ]);
    }

    public function update(Request $request, VideoResource $videoResource)
    {
        $data = $this->validateResource($request);
        $payload = [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'video_url' => $data['video_url'],
            'thumbnail_url' => $data['thumbnail_url'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'published_at' => $request->boolean('publish_now')
                ? ($videoResource->published_at ?? now())
                : null,
            'updated_by' => Auth::id(),
        ];

        if ($request->hasFile('thumbnail')) {
            Storage::disk('public')->delete($videoResource->thumbnail_path);
            $payload['thumbnail_path'] = $request->file('thumbnail')->store('video-thumbnails', 'public');
        }

        $videoResource->update($payload);

        $this->syncTargets($videoResource, $data['targets'] ?? []);

        return back()->with('flash', [
            'status' => 'success',
            'title' => 'Video Resource Updated',
            'message' => 'The video resource details and availability were updated.',
        ]);
    }

    public function destroy(VideoResource $videoResource)
    {
        $videoResource->delete();

        return back()->with('flash', [
            'status' => 'success',
            'title' => 'Video Resource Deleted',
            'message' => 'The video resource was removed from the list.',
        ]);
    }

    public function publicIndex(Request $request)
    {
        $resources = VideoResource::where('is_active', true)
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
            ->map(fn ($resource) => [
                'id' => $resource->id,
                'title' => $resource->title,
                'description' => $resource->description,
                'video_url' => $resource->video_url,
                'thumbnail_url' => $this->thumbnailUrl($resource),
                'published_at' => $resource->published_at?->toDateTimeString(),
            ]);

        return response()->json($resources);
    }

    private function validateResource(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'video_url' => ['required', 'url', 'max:2048'],
            'thumbnail_url' => ['nullable', 'url', 'max:2048'],
            'thumbnail' => ['nullable', 'image', 'max:5120'],
            'is_active' => ['boolean'],
            'publish_now' => ['boolean'],
            'targets' => ['required', 'array', 'min:1'],
            'targets.*.target_type' => ['required', Rule::in(['all', 'region', 'scholarship_program', 'program', 'school'])],
            'targets.*.target_id' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function syncTargets(VideoResource $resource, array $targets): void
    {
        $resource->targets()->delete();

        collect($targets)
            ->filter(fn ($target) => ($target['target_type'] ?? null) === 'all' || filled($target['target_id'] ?? null))
            ->unique(fn ($target) => ($target['target_type'] ?? '').':'.($target['target_id'] ?? ''))
            ->each(fn ($target) => $resource->targets()->create([
                'target_type' => $target['target_type'],
                'target_id' => $target['target_type'] === 'all' ? null : (string) $target['target_id'],
            ]));
    }

    private function thumbnailUrl(VideoResource $resource): ?string
    {
        if ($resource->thumbnail_path) {
            return Storage::disk('public')->url($resource->thumbnail_path);
        }

        return $resource->thumbnail_url;
    }
}
