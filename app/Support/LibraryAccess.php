<?php

namespace App\Support;

use App\Models\LocationRegions;
use App\Models\SchoolCampuses;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LibraryAccess
{
    public function options(?string $lockedRegionCode = null): array
    {
        $regions = LocationRegions::where('is_active', true)
            ->when($lockedRegionCode, fn ($query) => $query->where('code', $lockedRegionCode))
            ->orderBy('region')
            ->get()
            ->map(fn ($region) => [
                'id' => $region->code,
                'name' => $region->region ?? $region->name,
            ])
            ->values();

        $schools = SchoolCampuses::query()
            ->with('address:id,campus_id,region_code')
            ->where('is_active', true)
            ->where('is_delete', false)
            ->whereHas('address', fn ($query) => $query
                ->whereNotNull('region_code')
                ->when($lockedRegionCode, fn ($address) => $address->where('region_code', $lockedRegionCode)))
            ->orderBy('generated_name')
            ->get(['id', 'generated_name', 'name'])
            ->map(fn ($campus) => [
                'id' => $campus->id,
                'name' => $campus->generated_name ?: $campus->name,
                'region_id' => $campus->address?->region_code,
            ])
            ->values();

        return compact('regions', 'schools');
    }

    public function authorizeTargets(array $targets, ?User $user): array
    {
        $permissions = app(SystemPermissions::class);
        $lockedRegionCode = $permissions->shouldScopeToRegion($user)
            ? $permissions->regionCodeFor($user)
            : null;

        if (collect($targets)->contains(fn ($target) => ($target['target_type'] ?? null) === 'all')) {
            if ($lockedRegionCode) {
                throw ValidationException::withMessages([
                    'targets' => 'Regional users cannot publish content to everyone.',
                ]);
            }

            return [['target_type' => 'all', 'target_id' => null]];
        }

        $collection = collect($targets);
        $regionTargets = $collection->where('target_type', 'region')->pluck('target_id')->filter()->map(fn ($id) => (string) $id);
        $schoolTargets = $collection->where('target_type', 'school')->pluck('target_id')->filter()->map(fn ($id) => (int) $id);
        $validRegions = LocationRegions::whereIn('code', $regionTargets)
            ->where('is_active', true)
            ->pluck('code')
            ->map(fn ($code) => (string) $code);

        if ($validRegions->count() !== $regionTargets->unique()->count()) {
            throw ValidationException::withMessages([
                'targets' => 'One or more selected regions are unavailable.',
            ]);
        }

        $validSchools = SchoolCampuses::query()
            ->whereIn('id', $schoolTargets)
            ->where('is_active', true)
            ->where('is_delete', false)
            ->whereHas('address', fn ($query) => $query
                ->whereNotNull('region_code')
                ->when($lockedRegionCode, fn ($address) => $address->where('region_code', $lockedRegionCode)))
            ->pluck('id');

        if ($validSchools->count() !== $schoolTargets->unique()->count()) {
            throw ValidationException::withMessages([
                'targets' => 'One or more selected schools are unavailable for the selected region.',
            ]);
        }

        if ($lockedRegionCode && ($regionTargets->contains(fn ($code) => $code !== $lockedRegionCode))) {
            throw ValidationException::withMessages([
                'targets' => 'Regional users can only target their assigned region.',
            ]);
        }

        if ($lockedRegionCode && $regionTargets->isEmpty() && $schoolTargets->isEmpty()) {
            $regionTargets = collect([$lockedRegionCode]);
        }

        if ($regionTargets->isEmpty() && $schoolTargets->isEmpty()) {
            throw ValidationException::withMessages([
                'targets' => 'Select at least one region or school.',
            ]);
        }

        return $collection
            ->reject(fn ($target) => in_array($target['target_type'] ?? null, ['all', 'region', 'school'], true))
            ->concat($validRegions->map(fn ($id) => ['target_type' => 'region', 'target_id' => $id]))
            ->concat($validSchools->map(fn ($id) => ['target_type' => 'school', 'target_id' => (string) $id]))
            ->when(
                ! $collection->contains(fn ($target) => ($target['target_type'] ?? null) === 'scholarship_program'),
                fn ($items) => $items->push(['target_type' => 'scholarship_program', 'target_id' => 'all'])
            )
            ->when(
                ! $collection->contains(fn ($target) => ($target['target_type'] ?? null) === 'program'),
                fn ($items) => $items->push(['target_type' => 'program', 'target_id' => 'all'])
            )
            ->values()
            ->all();
    }

    public function applyPublicVisibility(Builder $query, Request $request): Builder
    {
        return $query->where(function ($audience) use ($request) {
            $audience->whereHas('targets', fn ($target) => $target->where('target_type', 'all'));

            $audience->orWhere(function ($scoped) use ($request) {
                $region = $request->input('region');
                $school = $request->input('school');

                $scoped->where(function ($geography) use ($region, $school) {
                    $geography->where(function ($regionMatch) use ($region) {
                        $regionMatch->whereHas('targets', function ($target) use ($region) {
                            $target->where('target_type', 'region')
                                ->where(function ($match) use ($region) {
                                    $match->where('target_id', 'all');
                                    if (filled($region)) {
                                        $match->orWhere('target_id', (string) $region);
                                    }
                                });
                        });
                    });

                    if (filled($school)) {
                        $geography->orWhereHas('targets', fn ($target) => $target
                            ->where('target_type', 'school')
                            ->where('target_id', (string) $school));
                    }
                });

                foreach (['scholarship_program', 'program'] as $type) {
                    $value = $request->input($type);
                    $scoped->where(function ($dimension) use ($type, $value) {
                        $dimension->whereDoesntHave('targets', fn ($target) => $target->where('target_type', $type))
                            ->orWhereHas('targets', function ($target) use ($type, $value) {
                                $target->where('target_type', $type)
                                    ->where(function ($match) use ($value) {
                                        $match->where('target_id', 'all');
                                        if (filled($value)) {
                                            $match->orWhere('target_id', (string) $value);
                                        }
                                    });
                            });
                    });
                }
            });
        });
    }
}
