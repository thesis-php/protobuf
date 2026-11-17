<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Protobuf\Exception\InvalidSchema;
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
            throw new InvalidSchema(\sprintf('Enum "%s" must be int-backed to be a protobuf enum.', $value::class));
        }

        // Enum numbers are int32: a negative one is sign-extended to 64 bits.
        SerdeInt64::T->serialize($buffer, Range::int32($case, 'enum'));
    }
}
