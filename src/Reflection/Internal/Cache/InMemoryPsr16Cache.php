<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection\Internal\Cache;

use Psr\SimpleCache\CacheInterface;

/**
 * @internal
 */
final class InMemoryPsr16Cache implements CacheInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $values = [];

    #[\Override]
    public function get(string $key, mixed $default = null): mixed
    {
        if (!\array_key_exists($key, $this->values)) {
            return $default;
        }

        return $this->values[$key];
    }

    #[\Override]
    public function set(string $key, mixed $value, null|\DateInterval|int $ttl = null): bool
    {
        $this->values[$key] = $value;

        return true;
    }

    #[\Override]
    public function delete(string $key): bool
    {
        unset($this->values[$key]);

        return true;
    }

    #[\Override]
    public function clear(): bool
    {
        $this->values = [];

        return true;
    }

    #[\Override]
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->get($key, $default);
        }

        return $values;
    }

    /**
     * @param iterable<mixed, mixed> $values
     */
    #[\Override]
    public function setMultiple(iterable $values, null|\DateInterval|int $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            \assert(\is_string($key), 'Cache key must be string');
            $this->values[$key] = $value;
        }

        return true;
    }

    #[\Override]
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    #[\Override]
    public function has(string $key): bool
    {
        return isset($this->values[$key]);
    }
}
