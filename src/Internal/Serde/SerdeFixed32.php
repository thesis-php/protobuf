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
enum SerdeFixed32 implements
    SerializeValue,
    DeserializeValue,
    SerdePacked
{
    case T;

    #[\Override]
    public function serialize(WriteBuffer $buffer, mixed $value): void
    {
        $buffer->write(Endian\Order::Little->packUint32(Range::uint32($value, 'fixed32')));
    }

    #[\Override]
    public function deserialize(ReadBuffer $buffer): int
    {
        return Endian\Order::Little->unpackUint32($buffer->read(4));
    }

    #[\Override]
    public function serializePacked(array $values): string
    {
        foreach ($values as $value) {
            /** @var int $value */
            Range::uint32($value, 'fixed32');
        }

        return pack('V*', ...$values);
    }

    #[\Override]
    public function deserializePacked(string $bytes): array
    {
        /** @var array<positive-int, int> */
        return unpack('V*', $bytes);
    }
}
