<?php

declare(strict_types=1);

namespace App\Shared\Cache\Public;

/**
 * In-memory cache backed by the RoadRunner KV plugin. Available to any module.
 */
interface CacheInterface
{
    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool;

    public function delete(string $key): bool;

    public function has(string $key): bool;
}
