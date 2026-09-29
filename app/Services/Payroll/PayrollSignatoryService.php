<?php

namespace App\Services\Payroll;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class PayrollSignatoryService
{
    private const REGIONAL_SIGNATORY_ROLE_IDS = [4, 5];

    public function options()
    {
        $user = Auth::user();
        $agencyId = $user?->profile?->agency_id;

        if (! $agencyId) {
            return collect();
        }

        return User::query()
            ->where('is_delete', false)
            ->where('is_active', true)
            ->whereIn('role_id', self::REGIONAL_SIGNATORY_ROLE_IDS)
            ->whereHas('profile', function ($profile) use ($agencyId) {
                $profile->where('agency_id', $agencyId);
            })
            ->with(['profile.agency'])
            ->orderBy('email')
            ->get()
            ->map(fn ($signatory) => [
                'id' => $signatory->id,
                'name' => $signatory->profile?->fullname ?? $signatory->email,
                'designation' => $signatory->profile?->designation,
                'agency' => $signatory->profile?->agency?->name,
            ])
            ->values();
    }

    public function find(int|string|null $id): ?array
    {
        if (! $id) {
            return null;
        }

        return $this->options()
            ->firstWhere('id', (int) $id);
    }

    public function findMany(array $ids): array
    {
        $allowedSignatories = $this->options()->keyBy('id');

        return collect($ids)
            ->map(fn ($id) => $allowedSignatories->get((int) $id))
            ->filter()
            ->values()
            ->all();
    }
}
