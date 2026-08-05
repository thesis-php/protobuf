<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use BcMath\Number;
use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;
use Thesis\Varint;

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
        SerdeVarint::T->serialize($buffer, Varint\BcMath::Codec->encodeZigZag(new Number($value)));
    }

    #[\Override]
    public function deserialize(ReadBuffer $buffer): int
    {
        return (int) Varint\BcMath::Codec->decodeZigZag(SerdeVarint::T->deserialize($buffer))->value;
    }
}
