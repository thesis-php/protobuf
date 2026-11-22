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
    private ?UnknownFields\Handler $unknowns = null;

    /** @var non-negative-int */
    private int $recursionLimit = Serializer::DEFAULT_RECURSION_LIMIT;

    private bool $validateUtf8 = true;

    /**
     * @deprecated Does nothing: the reflection of the classes is kept in memory for as long as the built instance
     *             lives, and nothing is stored in the cache any more. Will be removed in the next minor version.
     */
    public function withCache(CacheInterface $cache): self
    {
        return clone $this;
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

    /**
     * Sets whether a string field, a map key or value and a list element included, must be valid UTF-8, as protobuf
     * requires; decoding one that is not throws `Exception\MalformedUtf8`. Bytes fields are never checked.
     * The default is true.
     */
    public function withUtf8Validation(bool $validate): self
    {
        $builder = clone $this;
        $builder->validateUtf8 = $validate;

        return $builder;
    }

    public static function buildDefault(): Decoder
    {
        return new self()->build();
    }

    public function build(): Decoder
    {
        return new ReflectionDecoder(
            new Serializer($this->recursionLimit, $this->validateUtf8),
            Reflector::build(unknowns: $this->unknowns),
        );
    }
}
