<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use BcMath\Number;
use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;
use function Thesis\Protobuf\toNumber;

/**
 * @internal
 * @template-implements SerializeValue<Number|int|numeric-string>
 * @template-implements DeserializeValue<Number>
 */
enum SerdeInt32 implements
    SerializeValue,
    DeserializeValue
{
    case T;

    #[\Override]
    public function serialize(WriteBuffer $buffer, mixed $value): void
    {
        /** @var ?Number $p32 */
        static $p32;
        $p32 ??= new Number(2)->pow(32);

        /** @var ?Number $p64 */
        static $p64;
        $p64 ??= new Number(2)->pow(64);

        $num = toNumber($value)->mod($p32);

        if ($num->compare($p32->div(2)) >= 0) {
            $num -= $p32;
        }

        if ($num->compare(0) < 0) {
            $num += $p64;
        }

        SerdeVarint::T->serialize($buffer, $num);
    }

    #[\Override]
    public function deserialize(ReadBuffer $buffer): Number
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

        return $num;
    }
}
