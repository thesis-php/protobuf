<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;
use Thesis\Protobuf\Internal\Wire;

/**
 * @internal
 * @template-implements SerializeValue<int>
 * @template-implements DeserializeValue<int>
 */
enum SerdeInt32 implements
    SerializeValue,
    DeserializeValue
{
    case T;

    #[\Override]
    public function serialize(WriteBuffer $buffer, mixed $value): void
    {
        // A negative int32 is encoded as its 64-bit sign extension, exactly like an int64.
        Wire\writeVarint($buffer, Range::int32($value, 'int32'));
    }

    #[\Override]
    public function deserialize(ReadBuffer $buffer): int
    {
        // An int32 is the low 32 bits of the varint, sign-extended.
        $value = $buffer->readVarint() & 0xFFFF_FFFF;

        return $value > 0x7FFF_FFFF ? $value - 0x1_0000_0000 : $value;
    }
}
