<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Endian;
use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;

/**
 * @internal
 * @template-implements SerializeValue<float>
 * @template-implements DeserializeValue<float>
 * @template-implements SerdePacked<float>
 */
enum SerdeFloat implements
    SerializeValue,
    DeserializeValue,
    SerdePacked
{
    case T;

    #[\Override]
    public function serialize(WriteBuffer $buffer, mixed $value): void
    {
        $buffer->write(Endian\Order::Little->packFloat($value));
    }

    #[\Override]
    public function deserialize(ReadBuffer $buffer): float
    {
        return Endian\Order::Little->unpackFloat($buffer->read(4));
    }

    #[\Override]
    public function serializePacked(array $values): ?string
    {
        foreach ($values as $value) {
            if (!\is_float($value)) {
                return null;
            }
        }

        return pack('g*', ...$values);
    }

    #[\Override]
    public function deserializePacked(string $bytes): array
    {
        /** @var array<positive-int, float> */
        return unpack('g*', $bytes);
    }
}
