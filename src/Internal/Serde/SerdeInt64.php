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
enum SerdeInt64 implements
    SerializeValue,
    DeserializeValue
{
    case T;

    #[\Override]
    public function serialize(WriteBuffer $buffer, mixed $value): void
    {
        /** @var ?Number $p64 */
        static $p64;
        $p64 ??= new Number(2)->pow(64);

        /** @var ?Number $p63 */
        static $p63;
        $p63 ??= new Number(2)->pow(63);

        $num = toNumber($value)->mod($p64);

        if ($num->compare($p63) >= 0) {
            $num -= $p64;
        }

        if ($num->compare(0) < 0) {
            $num += $p64;
        }

        SerdeVarint::T->serialize($buffer, $num);
    }

    #[\Override]
    public function deserialize(ReadBuffer $buffer): Number
    {
        /** @var ?Number $p63 */
        static $p63;
        $p63 ??= new Number(2)->pow(63);

        /** @var ?Number $p64 */
        static $p64;
        $p64 ??= new Number(2)->pow(64);

        $num = SerdeVarint::T->deserialize($buffer);
        $num = $num->mod($p64);

        if ($num->compare($p63) >= 0) {
            $num -= $p64;
        }

        return $num;
    }
}
