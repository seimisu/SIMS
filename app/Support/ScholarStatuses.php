<?php

namespace App\Support;

use App\Models\ListStatuses;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ScholarStatuses
{
    public const TYPE_SCHOLAR = 'scholar';

    public const TYPE_STANDING = 'standing';

    public const ELIGIBLE_FOR_PAYROLL = ['NEW', 'ONGOING', 'GRADUATING'];

    public const BLOCKED_FROM_SERVICES = [
        'NON-COMPLIANCE',
        'NO REPORT',
        'LEAVE OF ABSENCE',
        'WITHDRAWN',
        'TERMINATED',
        'TERMINATED WITH SERVICE OBLIGATIONS',
        'DECEASED',
    ];

    public static function normalize(?string $status): string
    {
        return match (Str::upper(trim((string) $status))) {
            'LOA' => 'LEAVE OF ABSENCE',
            'WITHDREW' => 'WITHDRAWN',
            default => Str::upper(trim((string) $status)),
        };
    }

    public static function find(?string $status): ?ListStatuses
    {
        $name = self::normalize($status);

        if ($name === '') {
            return null;
        }

        return ListStatuses::query()
            ->where('type', self::TYPE_SCHOLAR)
            ->where('is_active', true)
            ->where('is_delete', false)
            ->whereRaw('UPPER(name) = ?', [$name])
            ->first();
    }

    public static function attributes(string $status): array
    {
        $reference = self::find($status);

        if (! $reference) {
            throw new \InvalidArgumentException("Scholar status '{$status}' is not configured in list_statuses.");
        }

        return [
            'status_id' => $reference->id,
            'academic_status' => Str::upper($reference->name),
        ];
    }

    public static function blocksServices(?string $status): bool
    {
        return in_array(self::normalize($status), self::BLOCKED_FROM_SERVICES, true);
    }
}
