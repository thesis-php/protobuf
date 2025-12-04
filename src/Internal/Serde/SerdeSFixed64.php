<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use BcMath\Number;
use Thesis\Endian;
use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;
use function Thesis\Protobuf\toNumber;

/**
 * @internal
 * @template-implements SerializeValue<Number|int|numeric-string>
 * @template-implements DeserializeValue<Number>
 */
enum SerdeSFixed64 implements
    SerializeValue,
    DeserializeValue
{
    case T;

    #[\Override]
    public function serialize(WriteBuffer $buffer, mixed $value): void
    {
        $buffer->write(Endian\Order::little->packInt64(toNumber($value)));
    }

    #[\Override]
    public function deserialize(ReadBuffer $buffer): Number
    {
        return Endian\Order::little->unpackInt64($buffer->read(8));
    }
}
