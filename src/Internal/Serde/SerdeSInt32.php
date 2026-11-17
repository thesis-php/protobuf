<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use BcMath\Number;
use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;

/**
 * @internal
 * @template-implements SerializeValue<int>
 * @template-implements DeserializeValue<int>
 */
enum SerdeSInt32 implements
    SerializeValue,
    DeserializeValue
{
    case T;

    #[\Override]
    public function serialize(WriteBuffer $buffer, mixed $value): void
    {
        // For values in the int32 range ZigZag-64 gives the same number as ZigZag-32.
        SerdeSInt64::T->serialize($buffer, Range::int32($value, 'sint32'));
    }

    #[\Override]
    public function deserialize(ReadBuffer $buffer): int
    {
        /** @var ?Number $p31 */
        static $p31;
        $p31 ??= new Number(2)->pow(31);

        /** @var ?Number $p32 */
        static $p32;
        $p32 ??= new Number(2)->pow(32);

        $num = new Number(SerdeSInt64::T->deserialize($buffer))->mod($p32);

        if ($num->compare($p31) >= 0) {
            $num -= $p32;
        }

        return (int) $num->value;
    }
}
