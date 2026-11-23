<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Endian;
use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;

/**
 * @internal
 * @template-implements SerializeValue<int>
 * @template-implements DeserializeValue<int>
 * @template-implements SerdePacked<int>
 */
enum SerdeSFixed32 implements
    SerializeValue,
    DeserializeValue,
    SerdePacked
{
    case T;

    #[\Override]
    public function serialize(WriteBuffer $buffer, mixed $value): void
    {
        $buffer->write(Endian\Order::Little->packInt32(Range::int32($value, 'sfixed32')));
    }

    #[\Override]
    public function deserialize(ReadBuffer $buffer): int
    {
        return Endian\Order::Little->unpackInt32($buffer->read(4));
    }

    #[\Override]
    public function serializePacked(array $values): string
    {
        foreach ($values as $value) {
            /** @var int $value */
            Range::int32($value, 'sfixed32');
        }

        // A negative int32 is written as its low 32 bits, as by Endian\Order::packInt32().
        return pack('V*', ...$values);
    }

    #[\Override]
    public function deserializePacked(string $bytes): array
    {
        /** @var array<positive-int, int> $unsigned */
        $unsigned = unpack('V*', $bytes);
        $values = [];

        foreach ($unsigned as $value) {
            $values[] = $value > 0x7FFF_FFFF ? $value - 0x1_0000_0000 : $value;
        }

        return $values;
    }
}
