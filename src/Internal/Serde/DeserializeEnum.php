<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Protobuf\Internal\Buffer\ReadBuffer;

/**
 * @internal
 * @template T of \BackedEnum
 * @template-implements DeserializeValue<T>
 */
final readonly class DeserializeEnum implements DeserializeValue
{
    /**
     * @param class-string<T> $enum
     */
    public function __construct(
        private string $enum,
    ) {}

    #[\Override]
    public function deserialize(ReadBuffer $buffer): \BackedEnum
    {
        $num = SerdeVarint::T->deserialize($buffer);

        return $this->enum::from((int) $num->value);
    }
}
