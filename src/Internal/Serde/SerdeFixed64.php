<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use BcMath\Number;
use Thesis\Endian;
use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;

/**
 * @internal
 * @template-implements SerializeValue<Number>
 * @template-implements DeserializeValue<Number>
 */
enum SerdeFixed64 implements
    SerializeValue,
    DeserializeValue
{
    case T;

    #[\Override]
    public function serialize(WriteBuffer $buffer, mixed $value): void
    {
        $buffer->write(Endian\Order::Little->packUint64($value));
    }

    #[\Override]
    public function deserialize(ReadBuffer $buffer): Number
    {
        return Endian\Order::Little->unpackUint64($buffer->read(8));
    }
}
