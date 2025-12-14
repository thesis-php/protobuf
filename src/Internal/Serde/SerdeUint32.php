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
enum SerdeUint32 implements
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

        SerdeVarint::T->serialize($buffer, new Number($value)->mod($p32));
    }

    #[\Override]
    public function deserialize(ReadBuffer $buffer): int
    {
        /** @var ?Number $p32 */
        static $p32;
        $p32 ??= new Number(2)->pow(32);

        $num = SerdeVarint::T->deserialize($buffer);
        $num = $num->mod($p32);

        return (int) $num->value;
    }
}
