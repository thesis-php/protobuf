<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;
use Thesis\Protobuf\Internal\Wire;
use Thesis\Protobuf\Message;
use Thesis\Protobuf\Tag;
use Thesis\Protobuf\WireType;

/**
 * A field holding a single value: a scalar, an enum or a message. The last occurrence of a scalar or an enum wins;
 * occurrences of a message are merged.
 *
 * @internal
 * @template T
 */
final readonly class SingularCodec implements FieldCodec
{
    /**
     * @param SerializeValue<T> $serializer
     * @param DeserializeValue<T|UnknownEnumValue> $deserializer
     */
    public function __construct(
        public SerializeValue $serializer,
        public DeserializeValue $deserializer,
        public WireType $wireType,
    ) {}

    #[\Override]
    public function encode(WriteBuffer $buffer, int $num, mixed $value): void
    {
        $buffer->write(Wire\tagBytes($num, $this->wireType));
        /** @var T $value */
        $this->serializer->serialize($buffer, $value);
    }

    #[\Override]
    public function accepts(WireType $type): bool
    {
        return $type === $this->wireType;
    }

    #[\Override]
    public function decode(ReadBuffer $buffer, Tag $tag, mixed &$slot, array &$unknowns): void
    {
        if ($slot instanceof Message && $this->deserializer instanceof DeserializeMessage) {
            $slot = $this->deserializer->merge($buffer, $slot);

            return;
        }

        $value = $this->deserializer->deserialize($buffer);

        if ($value instanceof UnknownEnumValue) {
            // The field keeps its previous value, if any.
            $unknowns[] = $value->toUnknownField($tag->num);
        } else {
            $slot = $value;
        }
    }
}
