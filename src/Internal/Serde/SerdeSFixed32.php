<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Endian;
use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;

/**
 * @internal
 * @phpstan-import-type Int32 from Endian\Order
 * @template-implements SerializeValue<Int32>
 * @template-implements DeserializeValue<Int32>
 */
enum SerdeSFixed32 implements
    SerializeValue,
    DeserializeValue
{
    case T;

    #[\Override]
    public function serialize(WriteBuffer $buffer, mixed $value): void
    {
        $buffer->write(Endian\Order::little->packInt32($value));
    }

    #[\Override]
    public function deserialize(ReadBuffer $buffer): int
    {
        return Endian\Order::little->unpackInt32($buffer->read(4));
    }
}
