<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Protobuf\Internal\Buffer\WriteBuffer;
use Thesis\Protobuf\Map;
use Thesis\Protobuf\Type;
use Thesis\Protobuf\Value;
use function Thesis\Protobuf\fieldOf;
use function Thesis\Protobuf\message;

/**
 * @internal
 * @template K
 * @template V
 * @template-implements SerializeValue<Map<K, V>>
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
        $messages = [];

        foreach ($value as $key => $val) {
            $messages[] = message(
                fieldOf(1, new Value($key, $this->type->keyT)),
                fieldOf(2, new Value($val, $this->type->valueT)),
            );
        }

        $this->serializer->serialize($buffer, $messages);
    }
}
