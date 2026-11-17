<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use BcMath\Number;
use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;
use Thesis\Protobuf\Internal\Wire;

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
        /** @var non-empty-string $bytes */
        $bytes = pack('P', Wire\fromUnsigned(Range::uint64($value, 'fixed64')));
        $buffer->write($bytes);
    }

    #[\Override]
    public function deserialize(ReadBuffer $buffer): Number
    {
        /** @var array{1: int} $value */
        $value = unpack('P', $buffer->read(8));

        return Wire\toUnsigned($value[1]);
    }
}
