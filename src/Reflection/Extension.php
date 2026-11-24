<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection;

use Thesis\Protobuf\Exception\InvalidSchema;

/**
 * An extension: a field that a schema declares outside the message it belongs to, with `extend`, under a number of the
 * extension range of that message. On the wire it is a field like any other; the class of the message does not declare
 * it, so the decoder keeps it among the unknown fields, where {@see \Thesis\Protobuf\Extensions} reads it from.
 *
 * PHP allows no `new` in a class constant: code that declares an extension once, as generated code does, returns it
 * from a static method that keeps it in a static variable, so that every call gives the same instance. PHPStan does
 * not infer the type of a static variable: a `@var` tag above it gives it.
 *
 * @api
 * @template-covariant T
 */
final readonly class Extension
{
    /**
     * @param class-string $extendee the class of the message the field extends
     * @param positive-int $num
     * @param Type<T, *, *, *> $type any type of a field but a map, which protobuf does not allow as an extension
     * @throws InvalidSchema if the type is a map
     */
    public function __construct(
        public string $extendee,
        public int $num,
        public Type $type,
    ) {
        if ($type instanceof MapT) {
            throw new InvalidSchema('An extension cannot be a map: protobuf has no such extensions.');
        }
    }
}
