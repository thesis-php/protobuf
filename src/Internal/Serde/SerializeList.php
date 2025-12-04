<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Protobuf\Internal\Buffer\ByteBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;
use Thesis\Protobuf\Internal\Tag;
use Thesis\Protobuf\Internal\WireType;

/**
 * @internal
 * @template T
 * @template-implements SerializeValue<list<T>>
 */
final readonly class SerializeList implements SerializeValue
{
    /**
     * @param SerializeValue<T> $serializer
     */
    public function __construct(
        private SerializeValue $serializer,
        private Tag $tag,
        private bool $isPacked,
    ) {}

    #[\Override]
    public function serialize(WriteBuffer $buffer, mixed $value): void
    {
        if ($this->isPacked) {
            $tag = new Tag($this->tag->num, WireType::bytes);
            $tmp = new ByteBuffer();

            foreach ($value as $it) {
                $this->serializer->serialize($tmp, $it);
            }

            if (\count($tmp) > 0) {
                $tag->serialize($buffer);
                copyBuffer($tmp, $buffer);
            }
        } else {
            foreach ($value as $it) {
                $this->tag->serialize($buffer);
                $this->serializer->serialize($buffer, $it);
            }
        }
    }
}
