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
enum SerdeSInt64 implements
    SerializeValue,
    DeserializeValue
{
    case T;

    #[\Override]
    public function serialize(WriteBuffer $buffer, mixed $value): void
    {
        // ZigZag: 0, -1, 1, -2... become 0, 1, 2, 3...
        Wire\writeVarint($buffer, ($value << 1) ^ ($value >> 63));
    }

    #[\Override]
    public function deserialize(ReadBuffer $buffer): int
    {
        $value = $buffer->readVarint();

        // A logical shift right, then the sign restored from the low bit.
        return (($value >> 1) & PHP_INT_MAX) ^ -($value & 1);
    }
}
