<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Endian;
use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;

/**
 * @internal
 * @template-implements SerializeValue<float>
 * @template-implements DeserializeValue<float>
 */
enum SerdeFloat implements
    SerializeValue,
    DeserializeValue
{
    case T;

    #[\Override]
    public function serialize(WriteBuffer $buffer, mixed $value): void
    {
        $buffer->write(Endian\Order::little->packFloat($value));
    }

    #[\Override]
    public function deserialize(ReadBuffer $buffer): float
    {
        return Endian\Order::little->unpackFloat($buffer->read(4));
    }
}
