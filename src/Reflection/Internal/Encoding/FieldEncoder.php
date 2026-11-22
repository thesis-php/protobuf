<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection\Internal\Encoding;

use Thesis\Protobuf\Value;

/**
 * How to encode a property declared as a field, built once per class.
 *
 * @internal
 */
final readonly class FieldEncoder
{
    /**
     * @param positive-int $num
     * @param \Closure(mixed): Value<mixed> $value converts the property value to a protobuf value
     * @param ?\Closure(mixed): bool $isEmpty whether the value is the default one, which is not written;
     *        null if every value but null is written
     */
    public function __construct(
        public \ReflectionProperty $property,
        public int $num,
        public \Closure $value,
        public ?\Closure $isEmpty,
    ) {}
}
