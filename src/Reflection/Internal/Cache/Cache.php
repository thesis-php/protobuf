<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection\Internal\Cache;

use Psr\SimpleCache\CacheInterface;
use Thesis\Protobuf\Type\MessageT;

/**
 * @internal
 */
final readonly class Cache
{
    public function __construct(
        private CacheInterface $cache,
    ) {}

    /**
     * @param class-string $class
     */
    public function set(string $class, MessageT $type): void
    {
        $this->cache->set($class, $type);
    }

    /**
     * @param class-string $class
     */
    public function get(string $class): ?MessageT
    {
        $messageT = $this->cache->get($class);
        if ($messageT instanceof MessageT) {
            return $messageT;
        }

        return null;
    }
}
