<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection\Internal\Encoding;

/**
 * How to encode a property declared as a oneof, built once per class.
 *
 * @internal
 */
final readonly class OneOfEncoder
{
    /**
     * @param non-empty-list<class-string> $variants
     */
    public function __construct(
        public \ReflectionProperty $property,
        public array $variants,
    ) {}
}
