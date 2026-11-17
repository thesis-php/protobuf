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
enum SerdeUint32 implements
    SerializeValue,
    DeserializeValue
{
    case T;

    #[\Override]
    public function serialize(WriteBuffer $buffer, mixed $value): void
    {
        Wire\writeVarint($buffer, Range::uint32($value, 'uint32'));
    }

    #[\Override]
    public function deserialize(ReadBuffer $buffer): int
    {
        return $buffer->readVarint() & 0xFFFF_FFFF;
    }
}
