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
    private ?CacheInterface $cache = null;

    public function withCache(CacheInterface $cache): self
    {
        $builder = clone $this;
        $builder->cache = $cache;

        return $builder;
    }

    public static function buildDefault(): Encoder
    {
        return new self()->build();
    }

    public function build(): Encoder
    {
        return new ReflectionEncoder(
            new Serializer(),
            Reflector::build($this->cache),
        );
    }
}
