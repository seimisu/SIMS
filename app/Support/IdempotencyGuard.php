<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

class IdempotencyGuard
{
    public static function forSeconds(string $key, int $seconds, Closure $callback): bool
    {
        if (! Cache::add("once:{$key}", true, $seconds)) {
            return false;
        }

        try {
            $callback();

            return true;
        } catch (\Throwable $throwable) {
            Cache::forget("once:{$key}");

            throw $throwable;
        }
    }
}
