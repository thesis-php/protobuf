<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Wire;

/**
 * @internal
 * @template T of \BackedEnum
 * @template-implements DeserializeValue<T|UnknownEnumValue>
 */
final readonly class DeserializeEnum implements DeserializeValue
{
    /**
     * @param class-string<T> $enum
     */
    public function __construct(
        private string $enum,
    ) {}

    /**
     * @return T|UnknownEnumValue
     */
    #[\Override]
    public function deserialize(ReadBuffer $buffer): \BackedEnum|UnknownEnumValue
    {
        $raw = $buffer->readVarint();

        // Enum numbers are int32: negative ones arrive sign-extended to 64 bits.
        $num = $raw & 0xFFFF_FFFF;
        if ($num > 0x7FFF_FFFF) {
            $num -= 0x1_0000_0000;
        }

        return $this->enum::tryFrom($num) ?? new UnknownEnumValue(Wire\toUnsigned($raw));
    }
}
