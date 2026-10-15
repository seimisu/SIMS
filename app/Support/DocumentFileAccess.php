<?php

namespace App\Support;

use App\Models\Document;
use App\Models\SchoolCampuses;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class DocumentFileAccess
{
    public function visibleTo(Builder $query, ?User $user): Builder
    {
        $permissions = app(SystemPermissions::class);
        if ($user && $permissions->can($user, 'documents.view')) {
            if (! $permissions->shouldScopeToRegion($user)) {
                return $query;
            }

            $region = $permissions->regionCodeFor($user);
            if (! $region) {
                return $query->whereRaw('1 = 0');
            }
            $schools = SchoolCampuses::notDeleted()
                ->whereHas('address', fn ($address) => $address->where('region_code', $region))
                ->pluck('id')->map(fn ($id) => (string) $id)->all();

            return $query->whereHas('targets', fn ($targets) => $targets
                ->where('target_type', 'all')
                ->orWhere(fn ($target) => $target->where('target_type', 'region')->whereIn('target_id', ['all', $region]))
                ->orWhere(fn ($target) => $target->where('target_type', 'school')->whereIn('target_id', $schools)));
        }

        // Anonymous callers cannot establish audience membership using query parameters.
        return $query->where('is_active', true)->whereNotNull('published_at')->where('published_at', '<=', now())
            ->whereHas('targets', fn ($target) => $target->where('target_type', 'all'));
    }

    public function authorize(Document $document, ?User $user): void
    {
        abort_unless($this->visibleTo(Document::query(), $user)->whereKey($document->id)->exists(), 404);
    }
}
