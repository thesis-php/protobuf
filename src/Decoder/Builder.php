<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Decoder;

use Psr\SimpleCache\CacheInterface;
use Thesis\Protobuf\Decoder;
use Thesis\Protobuf\Decoder\Internal\ReflectionDecoder;
use Thesis\Protobuf\Reflection\Reflector;
use Thesis\Protobuf\Serializer;
use Thesis\Protobuf\UnknownFields;

/**
 * @api
 */
final class Builder
{
    private ?CacheInterface $cache = null;

    private ?UnknownFields\Handler $unknowns = null;

    /** @var non-negative-int */
    private int $recursionLimit = Serializer::DEFAULT_RECURSION_LIMIT;

    public function withCache(CacheInterface $cache): self
    {
        $builder = clone $this;
        $builder->cache = $cache;

        return $builder;
    }

    public function withUnknownHandler(UnknownFields\Handler $handler): self
    {
        $builder = clone $this;
        $builder->unknowns = $handler;

        return $builder;
    }

    /**
     * Sets how many messages and groups the input may nest inside the top-level message, one in another, unknown
     * fields included; decoding a deeper input throws `Exception\RecursionLimitExceeded`.
     * The default is `Serializer::DEFAULT_RECURSION_LIMIT`.
     *
     * @param int $limit not negative
     * @throws \InvalidArgumentException if the limit is negative
     */
    public function withRecursionLimit(int $limit): self
    {
        if ($limit < 0) {
            throw new \InvalidArgumentException("The recursion limit must not be negative, got {$limit}.");
        }

        $builder = clone $this;
        $builder->recursionLimit = $limit;

        return $builder;
    }

    public static function buildDefault(): Decoder
    {
        return new self()->build();
    }

    public function build(): Decoder
    {
        return new ReflectionDecoder(
            new Serializer($this->recursionLimit),
            Reflector::build($this->cache, $this->unknowns),
        );
    }
}
