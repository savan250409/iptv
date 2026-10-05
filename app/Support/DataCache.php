<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Lightweight, driver-agnostic cache for read-heavy data (API + dashboard).
 *
 * Keys are prefixed with a global "version" number. Any write bumps the
 * version, which instantly invalidates every cached read without needing
 * cache tags (so it works with the default file/database cache driver).
 */
class DataCache
{
    private const VERSION_KEY = 'data_version';

    public static function version(): int
    {
        $v = Cache::get(self::VERSION_KEY);
        if ($v === null) {
            Cache::forever(self::VERSION_KEY, 1);
            return 1;
        }
        return (int) $v;
    }

    /** Call after any create/update/delete so caches refresh immediately. */
    public static function bump(): void
    {
        self::version();              // ensure the key exists
        Cache::increment(self::VERSION_KEY);
    }

    /** Remember a value under a version-scoped key. */
    public static function remember(string $key, int $ttlSeconds, Closure $callback)
    {
        return Cache::remember('v' . self::version() . ':' . $key, $ttlSeconds, $callback);
    }
}
