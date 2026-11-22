<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;
use Thesis\Protobuf\Internal\Wire;

/**
 * @internal
 * @template-implements SerializeValue<bool>
 * @template-implements DeserializeValue<bool>
 */
enum SerdeBool implements
    SerializeValue,
    DeserializeValue
{
    case T;

    #[\Override]
    public function serialize(WriteBuffer $buffer, mixed $value): void
    {
        Wire\writeVarint($buffer, $value ? 1 : 0);
    }

    #[\Override]
    public function deserialize(ReadBuffer $buffer): bool
    {
        return $buffer->readVarint() !== 0;
    }
}
