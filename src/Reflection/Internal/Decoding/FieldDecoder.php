<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection\Internal\Decoding;

use Thesis\Protobuf\Reflection\Internal\Api\DefaultPropertyValue;

/**
 * How to decode a property declared as a field, built once per class.
 *
 * @internal
 */
final readonly class FieldDecoder
{
    /**
     * @param positive-int $num
     * @param ?\Closure(mixed): mixed $value converts the decoded value to the property value; null keeps it as is
     * @param ?DefaultPropertyValue $default the value of the property when the field is missing; null if it has none
     * @param ?\Closure(): mixed $zero gives the value of the property when the field is missing and it has no default,
     *        if missing fields are zero values; null if it is required
     */
    public function __construct(
        public \ReflectionProperty $property,
        public int $num,
        public ?\Closure $value,
        public ?DefaultPropertyValue $default,
        public ?\Closure $zero = null,
    ) {}
}
