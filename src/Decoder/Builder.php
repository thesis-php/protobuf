<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Decoder;

use Psr\SimpleCache\CacheInterface;
use Thesis\Protobuf\Decoder;
use Thesis\Protobuf\Decoder\Internal\ReflectionDecoder;
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

    public static function buildDefault(): Decoder
    {
        return new self()->build();
    }

    public function build(): Decoder
    {
        return new ReflectionDecoder(
            new Serializer(),
            Reflector::build($this->cache),
        );
    }
}
