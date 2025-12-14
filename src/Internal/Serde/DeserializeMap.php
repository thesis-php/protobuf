<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Protobuf\FieldDescriptor;
use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Map;
use Thesis\Protobuf\Message;

/**
 * @internal
 * @template K
 * @template V
 * @template-implements DeserializeValue<Map<K, V>>
 */
final readonly class DeserializeMap implements DeserializeValue
{
    /**
     * @param DeserializeValue<list<Message>> $deserializer
     */
    public function __construct(
        private DeserializeValue $deserializer,
    ) {}

    #[\Override]
    public function deserialize(ReadBuffer $buffer): Map
    {
        $values = $this->deserializer->deserialize($buffer);

        /** @var Map<K, V> $map */
        $map = new Map();

        foreach ($values as $value) {
            /** @var ?FieldDescriptor<K> $key */
            $key = $value->fields[1] ?? null;
            /** @var ?FieldDescriptor<V> $val */
            $val = $value->fields[2] ?? null;

            if ($key !== null && $val !== null) {
                $map[$key->value->value] = $val->value->value;
            }
        }

        return $map;
    }
}
