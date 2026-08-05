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
        /** @var ?Number $p31 */
        static $p31;

        /** @var ?Number $p32 */
        static $p32;

        $p31 ??= new Number(2)->pow(31);
        $p32 ??= new Number(2)->pow(32);

        $num = new Number($value)->mod($p32);

        if ($num->compare($p31) >= 0) {
            $num -= $p32;
        }

        SerdeSInt64::T->serialize($buffer, (int) $num->value);
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
