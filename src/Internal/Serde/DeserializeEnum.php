<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use BcMath\Number;
use Thesis\Protobuf\Internal\Buffer\ReadBuffer;

/**
 * @internal
 * @template T of \BackedEnum
 * @template-implements DeserializeValue<T|UnknownEnumValue>
 */
final readonly class DeserializeEnum implements DeserializeValue
{
    /**
     * @param class-string<T> $enum
     */
    public function __construct(
        private string $enum,
    ) {}

    /**
     * @return T|UnknownEnumValue
     */
    #[\Override]
    public function deserialize(ReadBuffer $buffer): \BackedEnum|UnknownEnumValue
    {
        /** @var ?Number $p31 */
        static $p31;
        $p31 ??= new Number(2)->pow(31);

        /** @var ?Number $p32 */
        static $p32;
        $p32 ??= new Number(2)->pow(32);

        $raw = SerdeVarint::T->deserialize($buffer);

        // Enum numbers are int32: negative ones arrive sign-extended to 64 bits.
        $num = $raw->mod($p32);
        if ($num->compare($p31) >= 0) {
            $num -= $p32;
        }

        return $this->enum::tryFrom((int) $num->value) ?? new UnknownEnumValue($raw);
    }
}
