<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Endian;
use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;

/**
 * @internal
 * @template-implements SerializeValue<int>
 * @template-implements DeserializeValue<int>
 */
enum SerdeSFixed32 implements
    SerializeValue,
    DeserializeValue
{
    case T;

    #[\Override]
    public function serialize(WriteBuffer $buffer, mixed $value): void
    {
        $buffer->write(Endian\Order::Little->packInt32($value));
    }

    #[\Override]
    public function deserialize(ReadBuffer $buffer): int
    {
        return Endian\Order::Little->unpackInt32($buffer->read(4));
    }
}
