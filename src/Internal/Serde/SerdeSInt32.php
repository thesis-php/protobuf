<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;

/**
 * @internal
 * @template-implements SerializeValue<int>
 * @template-implements DeserializeValue<int>
 */
enum SerdeSInt32 implements
    SerializeValue,
    DeserializeValue
{
    case T;

    #[\Override]
    public function serialize(WriteBuffer $buffer, mixed $value): void
    {
        // For values in the int32 range ZigZag-64 gives the same number as ZigZag-32.
        SerdeSInt64::T->serialize($buffer, Range::int32($value, 'sint32'));
    }

    #[\Override]
    public function deserialize(ReadBuffer $buffer): int
    {
        // A sint32 is ZigZag-32 of the low 32 bits of the varint.
        $value = $buffer->readVarint() & 0xFFFF_FFFF;

        return ($value >> 1) ^ -($value & 1);
    }
}
