<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;

/**
 * @internal
 * @template-implements SerializeValue<int>
 * @template-implements DeserializeValue<int>
 */
enum SerdeSFixed64 implements
    SerializeValue,
    DeserializeValue
{
    case T;

    #[\Override]
    public function serialize(WriteBuffer $buffer, mixed $value): void
    {
        /** @var non-empty-string $bytes */
        $bytes = pack('P', $value);
        $buffer->write($bytes);
    }

    #[\Override]
    public function deserialize(ReadBuffer $buffer): int
    {
        /** @var array{1: int} $value */
        $value = unpack('P', $buffer->read(8));

        return $value[1];
    }
}
