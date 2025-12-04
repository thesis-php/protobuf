<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use BcMath\Number;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;

/**
 * @internal
 * @template-implements SerializeValue<\BackedEnum>
 */
enum SerializeEnum implements SerializeValue
{
    case T;

    #[\Override]
    public function serialize(WriteBuffer $buffer, mixed $value): void
    {
        $case = $value->value;
        if (!\is_int($case)) {
            throw new \InvalidArgumentException('BackedEnum should be integer.');
        }

        SerdeVarint::T->serialize($buffer, new Number($case));
    }
}
