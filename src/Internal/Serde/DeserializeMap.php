<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Protobuf\FieldDescriptor;
use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Polyfill;
use Thesis\Protobuf\Internal\Schema\Type;
use Thesis\Protobuf\Message;
use Thesis\Protobuf\NumberedKeyArray;

/**
 * @internal
 * @template K
 * @template V
 * @template-implements DeserializeValue<\ArrayAccess<K, V>>
 */
final readonly class DeserializeMap implements DeserializeValue
{
    /**
     * @param DeserializeValue<list<Message>> $deserializer
     * @param Type\MapT<K, V> $type
     */
    public function __construct(
        private DeserializeValue $deserializer,
        private Type\MapT $type,
    ) {}

    #[\Override]
    public function deserialize(ReadBuffer $buffer): \ArrayAccess
    {
        $values = $this->deserializer->deserialize($buffer);

        $map = $this->createArray();

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

    /**
     * @return \ArrayAccess<K, V>
     */
    private function createArray(): \ArrayAccess
    {
        if ($this->type->keyT->accept(new Type\Visitor\IsNumber())) {
            /** @phpstan-ignore return.type */
            return new NumberedKeyArray(new Polyfill\NumberedKeySplObjectStorage());
        }

        return new \ArrayObject();
    }
}
