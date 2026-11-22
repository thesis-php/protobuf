<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Encoder;

use Psr\SimpleCache\CacheInterface;
use Thesis\Protobuf\Encoder;
use Thesis\Protobuf\Encoder\Internal\ReflectionEncoder;
use Thesis\Protobuf\Reflection\Reflector;
use Thesis\Protobuf\Serializer;
use Thesis\Protobuf\UnknownFields;

/**
 * @api
 */
final class Builder
{
    private ?UnknownFields\Provider $unknowns;

    private bool $validateUtf8 = true;

    public function __construct()
    {
        $this->unknowns = UnknownFields::handler();
    }

    /**
     * @deprecated Does nothing: the reflection of the classes is kept in memory for as long as the built instance
     *             lives, and nothing is stored in the cache any more. Will be removed in the next minor version.
     */
    public function withCache(CacheInterface $cache): self
    {
        return clone $this;
    }

    /**
     * Sets where the unknown fields of each object encoded, nested ones included, are taken from: they are written
     * after its known fields. The default is `UnknownFields::handler()`, which holds the fields of the objects decoded
     * by a decoder built `withUnknownHandler(UnknownFields::handler())`, and nothing for any other object.
     * Null writes no unknown fields and does not look them up.
     */
    public function withUnknownProvider(?UnknownFields\Provider $provider): self
    {
        $builder = clone $this;
        $builder->unknowns = $provider;

        return $builder;
    }

    /**
     * Sets whether a string property, a map key or value and a list element included, must be valid UTF-8, as
     * protobuf requires; encoding one that is not throws `Exception\InvalidUtf8`. Bytes fields are never checked.
     * The default is true.
     */
    public function withUtf8Validation(bool $validate): self
    {
        $builder = clone $this;
        $builder->validateUtf8 = $validate;

        return $builder;
    }

    public static function buildDefault(): Encoder
    {
        return new self()->build();
    }

    public function build(): Encoder
    {
        return new ReflectionEncoder(
            new Serializer(validateUtf8: $this->validateUtf8),
            Reflector::build(unknownProvider: $this->unknowns),
        );
    }
}
