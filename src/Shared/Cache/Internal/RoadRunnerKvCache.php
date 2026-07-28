<?php

declare(strict_types=1);

namespace App\Shared\Cache\Internal;

use App\Shared\Cache\Public\CacheInterface;
use Spiral\RoadRunner\KeyValue\Factory;
use Spiral\RoadRunner\KeyValue\StorageInterface;

final readonly class RoadRunnerKvCache implements CacheInterface
{
    private const string STORAGE_NAME = 'local-cache';

    private StorageInterface $storage;

    public function __construct(Factory $factory)
    {
        $this->storage = $factory->select(self::STORAGE_NAME);
    }

    #[\Override]
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->storage->get($key, $default);
    }

    #[\Override]
    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
    {
        return $this->storage->set($key, $value, $ttl);
    }

    #[\Override]
    public function delete(string $key): bool
    {
        return $this->storage->delete($key);
    }

    #[\Override]
    public function has(string $key): bool
    {
        return $this->storage->has($key);
    }
}
