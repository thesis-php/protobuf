<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use BcMath\Number;
use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;
use function Thesis\Protobuf\toNumber;

/**
 * @internal
 * @template-implements SerializeValue<Number|int|numeric-string>
 * @template-implements DeserializeValue<Number>
 */
enum SerdeUint64 implements
    SerializeValue,
    DeserializeValue
{
    case T;

    #[\Override]
    public function serialize(WriteBuffer $buffer, mixed $value): void
    {
        SerdeVarint::T->serialize($buffer, toNumber($value));
    }

    #[\Override]
    public function deserialize(ReadBuffer $buffer): Number
    {
        return SerdeVarint::T->deserialize($buffer);
    }
}
