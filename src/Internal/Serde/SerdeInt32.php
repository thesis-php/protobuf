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
enum SerdeInt32 implements
    SerializeValue,
    DeserializeValue
{
    case T;

    #[\Override]
    public function serialize(WriteBuffer $buffer, mixed $value): void
    {
        // A negative int32 is encoded as its 64-bit sign extension, exactly like an int64.
        SerdeInt64::T->serialize($buffer, Range::int32($value, 'int32'));
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

        $num = SerdeVarint::T->deserialize($buffer);
        $num = $num->mod($p32);

        if ($num->compare($p31) >= 0) {
            $num -= $p32;
        }

        return (int) $num->value;
    }
}
