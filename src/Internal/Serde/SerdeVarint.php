<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use BcMath\Number;
use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;
use Thesis\Varint;

/**
 * @internal
 * @template-implements SerializeValue<Number>
 * @template-implements DeserializeValue<Number>
 */
enum SerdeVarint implements
    SerializeValue,
    DeserializeValue
{
    case T;

    #[\Override]
    public function serialize(WriteBuffer $buffer, mixed $value): void
    {
        $buffer->write(Varint\BcMath::Codec->encodeVarint($value));
    }

    #[\Override]
    public function deserialize(ReadBuffer $buffer): Number
    {
        $number = Varint\BcMath::Codec->decodeVarintSized($buffer->peek(10));
        $buffer->read($number->size);

        return $number->value;
    }
}
