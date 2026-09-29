<?php

namespace App\Support;

final class GradingSystem
{
    public const TRANSMUTATION = 'Transmutation';

    public const PERCENT = 'Percent Grading';

    public const SUPPORTED = [
        self::TRANSMUTATION,
        self::PERCENT,
    ];

    public static function isPercent(?string $name): bool
    {
        return $name === self::PERCENT;
    }
}
