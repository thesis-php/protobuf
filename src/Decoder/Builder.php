<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Decoder;

use Thesis\Protobuf\Decoder;
use Thesis\Protobuf\Decoder\Internal\ReflectionDecoder;
use Thesis\Protobuf\Exception\InvalidSchema;
use Thesis\Protobuf\Extensions;
use Thesis\Protobuf\Reflection\Extension;
use Thesis\Protobuf\Reflection\ExtensionRegistry;
use Thesis\Protobuf\Reflection\Reflector;
use Thesis\Protobuf\Serializer;
use Thesis\Protobuf\UnknownFields;

/**
 * @api
 */
final class Builder
{
    private ?UnknownFields\Handler $unknowns;

    /** @var non-negative-int */
    private int $recursionLimit = Serializer::DEFAULT_RECURSION_LIMIT;

    private bool $validateUtf8 = true;

    private bool $missingFieldsAsZeroValues = false;

    /** @var list<Extension<mixed>> */
    private array $extensions = [];

    /**
     * The registry of the extensions with the options of the builder, which its decoders and its `Extensions` share,
     * with the values of the extensions the decoders read: each builder has its own, since the values depend on
     * the options.
     */
    private ?ExtensionRegistry $registry = null;

    public function __construct()
    {
        $this->unknowns = UnknownFields::handler();
    }

    public function __clone()
    {
        $this->registry = null;
    }

    /**
     * Sets what gets the unknown fields of each object decoded that has any, nested ones included: the fields of
     * the input that its class does not declare, extensions among them, or that are not written as declared.
     * The default is `UnknownFields::handler()`, which keeps them for as long as the object lives, so that the encoder
     * writes them back and buildExtensions() reads the extensions from them. Null drops them.
     */
    public function withUnknownHandler(?UnknownFields\Handler $handler): self
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

    /**
     * Sets whether a missing field gives a property that has no default and cannot be null the zero value of the
     * field, as proto3 reads a field without presence: a proto3 sender does not write a zero value, so such a property
     * is otherwise required (see `Reflection\Exception\PropertyRequired`) and fails on every zero. The zero value is
     * 0, `new BcMath\Number(0)` for uint64 and fixed64, 0.0, false, '', an empty list, an empty map, as a `Map` or
     * an array as the property holds it, and the enum case for 0 or, failing that, the first case. Nested objects,
     * oneof variants included, are decoded the same way.
     *
     * A message field and an enum without cases have no zero value: a property for one with no default that cannot be
     * null stays required, as proto3 tells an absent message from an empty one. A oneof with no variant in the input
     * is null, as without the option. A default of the property, and null for one that can be null, still come first.
     * The default is false.
     */
    public function withMissingFieldsAsZeroValues(bool $zeroValues): self
    {
        $builder = clone $this;
        $builder->missingFieldsAsZeroValues = $zeroValues;

        return $builder;
    }

    /**
     * Registers extensions in the decoder, in addition to those registered before: when it decodes an object of
     * the class an extension extends, nested ones included, it reads the extension among the unknown fields of
     * the object, if it is there, as `Extensions::get()` reads it, so that a malformed one, such as a string that is
     * not valid UTF-8, fails the decoding with the exception get() would throw, the field numbers and the properties
     * located from the object extended. The unknown fields of a oneof variant are those of the object that holds it.
     * The messages and groups of the value count towards the recursion limit with the messages the object is nested
     * in, as if the extension were a field of the object. The decoder reads no other extensions.
     *
     * The extensions stay among the unknown fields: the unknown handler gets them, `Extensions` reads and writes them
     * as without the registration, and the encoder writes them back. `Extensions` built by buildExtensions() of
     * the same builder without a provider gets the values that its decoders read from then on, instead of decoding
     * them again; another builder, one a with*() method returns included, has values of its own.
     *
     * An object of a class that no extension extends, or without unknown fields, costs nothing more to decode. An
     * extension extends a class as `Extensions` checks it, with `instanceof`.
     *
     * @param Extension<mixed> ...$extensions an instance registered again is registered once
     * @throws InvalidSchema if two different instances extend the same class with the same number; two that extend
     *         a class and its parent, or an interface it implements, with the same number fail the decoding of
     *         an object of the class instead
     */
    public function withExtensions(Extension ...$extensions): self
    {
        $builder = clone $this;
        $builder->extensions = new ExtensionRegistry([...$this->extensions, ...$extensions])->extensions;

        return $builder;
    }

    public static function buildDefault(): Decoder
    {
        return new self()->build();
    }

    public function build(): Decoder
    {
        return new ReflectionDecoder($this->serializer(), $this->reflector());
    }

    /**
     * Builds the reader and writer of the extensions of the objects that the decoders of the builder decode, with
     * the same options: an extension is read from the unknown fields of the object as the decoder would read it as
     * a field, and the objects of its value get their unknown fields from the handler of the builder. A value set is
     * checked as it will be read: its strings for UTF-8 if the decoder checks them.
     *
     * @param ?UnknownFields\Provider $unknowns gives the unknown fields of an object; by default the handler of
     *        the builder, which must then be a provider of the fields it gets, as `UnknownFields::handler()` is.
     *        To set extensions, it must be an `UnknownFields\Store`, as `UnknownFields::handler()` is too, and the
     *        encoder must take the unknown fields from it. If none is given, the values of the registered extensions
     *        that the decoders of the builder read from then on are kept for it, see withExtensions()
     * @throws \LogicException if no provider is given and the handler of the builder is not one, or is null
     */
    public function buildExtensions(?UnknownFields\Provider $unknowns = null): Extensions
    {
        $values = null;

        if ($unknowns === null) {
            if (!$this->unknowns instanceof UnknownFields\Provider) {
                throw new \LogicException(\sprintf(
                    'Extensions are read from the unknown fields of an object, but the unknown handler of the builder (%s) does not provide them: pass the provider of the fields it gets.',
                    $this->unknowns === null ? 'null' : $this->unknowns::class,
                ));
            }

            $unknowns = $this->unknowns;

            // The values the decoders read are those get() would read from the fields the handler got.
            $values = $this->registry()?->values();
        }

        return new Extensions(
            $this->serializer(),
            Reflector::build(
                unknowns: $this->unknowns,
                unknownProvider: $unknowns,
                missingFieldsAsZeroValues: $this->missingFieldsAsZeroValues,
            ),
            $unknowns,
            $values,
        );
    }

    private function serializer(): Serializer
    {
        return new Serializer($this->recursionLimit, $this->validateUtf8);
    }

    private function reflector(): Reflector
    {
        return Reflector::build(
            unknowns: $this->unknowns,
            missingFieldsAsZeroValues: $this->missingFieldsAsZeroValues,
            extensions: $this->registry(),
        );
    }

    /**
     * Null if no extensions are registered: the decoders then read none.
     */
    private function registry(): ?ExtensionRegistry
    {
        return $this->extensions === []
            ? null
            : $this->registry ??= new ExtensionRegistry($this->extensions, $this->recursionLimit, $this->validateUtf8);
    }
}
