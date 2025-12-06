<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use BcMath\Number;
use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;
use Thesis\Protobuf\Internal\Wire;
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
        return Wire\readVarint($buffer);
    }
}
