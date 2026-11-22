<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection\Internal\Decoding;

/**
 * How to decode a property declared as a oneof, built once per class.
 *
 * @internal
 */
final readonly class OneOfDecoder
{
    /**
     * @param array<positive-int, class-string> $variants the variant each field number belongs to, in the order of
     *        the variants: the first variant with a field in the message is chosen, though a message read with
     *        the type of the class holds the fields of one variant at most
     */
    public function __construct(
        public \ReflectionProperty $property,
        public array $variants,
    ) {}
}
