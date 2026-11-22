<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Encoder;

use Psr\SimpleCache\CacheInterface;
use Thesis\Protobuf\Encoder;
use Thesis\Protobuf\Encoder\Internal\ReflectionEncoder;
use Thesis\Protobuf\Reflection\Reflector;
use Thesis\Protobuf\Serializer;

/**
 * @api
 */
final class Builder
{
    /**
     * @deprecated Does nothing: the reflection of the classes is kept in memory for as long as the built instance
     *             lives, and nothing is stored in the cache any more. Will be removed in the next minor version.
     */
    public function withCache(CacheInterface $cache): self
    {
        return clone $this;
    }

    public static function buildDefault(): Encoder
    {
        return new self()->build();
    }

    public function build(): Encoder
    {
        return new ReflectionEncoder(
            new Serializer(),
            Reflector::build(),
        );
    }
}
