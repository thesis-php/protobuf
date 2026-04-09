<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use BcMath\Number;
use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;

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
        SerdeVarint::T->serialize($buffer, new Number((int) $value));
    }

    #[\Override]
    public function deserialize(ReadBuffer $buffer): bool
    {
        $num = SerdeVarint::T->deserialize($buffer);

        return (int) $num->value !== 0;
    }
}
