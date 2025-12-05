<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Protobuf\Internal\Buffer\WriteBuffer;
use Thesis\Protobuf\Internal\Schema\Type;
use Thesis\Protobuf\Value;
use function Thesis\Protobuf\fieldOf;
use function Thesis\Protobuf\message;

/**
 * @internal
 * @template K of array-key
 * @template V
 * @template-implements SerializeValue<array<K, V>>
 */
final readonly class SerializeMap implements SerializeValue
{
    /**
     * @param SerializeValue<list<*>> $serializer
     * @param Type\MapT<K, V> $type
     */
    public function __construct(
        private SerializeValue $serializer,
        private Type\MapT $type,
    ) {}

    #[\Override]
    public function serialize(WriteBuffer $buffer, mixed $value): void
    {
        $this->serializer->serialize($buffer, array_map(
            fn(mixed $key, mixed $value) => message(
                fieldOf(1, new Value($key, $this->type->keyT)),
                fieldOf(2, new Value($value, $this->type->valueT)),
            ),
            array_keys($value),
            array_values($value),
        ));
    }
}
