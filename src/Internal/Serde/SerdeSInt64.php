<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use BcMath\Number;
use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;
use Thesis\Varint;
use function Thesis\Protobuf\toNumber;

/**
 * @internal
 * @template-implements SerializeValue<Number|int|numeric-string>
 * @template-implements DeserializeValue<Number>
 */
enum SerdeSInt64 implements
    SerializeValue,
    DeserializeValue
{
    case T;

    #[\Override]
    public function serialize(WriteBuffer $buffer, mixed $value): void
    {
        SerdeVarint::T->serialize($buffer, Varint\BcMath::Codec->encodeZigZag(toNumber($value)));
    }

    #[\Override]
    public function deserialize(ReadBuffer $buffer): Number
    {
        return Varint\BcMath::Codec->decodeZigZag(SerdeVarint::T->deserialize($buffer));
    }
}
