<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Protobuf\Internal\Buffer;
use Thesis\Protobuf\Internal\Buffer\ByteBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;
use Thesis\Protobuf\Internal\Wire;
use Thesis\Protobuf\Tag;
use Thesis\Protobuf\WireType;

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
        private bool $isPacked = false,
    ) {}

    #[\Override]
    public function serialize(WriteBuffer $buffer, mixed $value): void
    {
        if ($this->isPacked) {
            $tag = new Tag($this->tag->num, WireType::BYTES);
            $tmp = new ByteBuffer();

            foreach ($value as $it) {
                $this->serializer->serialize($tmp, $it);
            }

            if (\count($tmp) > 0) {
                Wire\writeTag($buffer, $tag);
                Buffer\copy($tmp, $buffer);
            }
        } else {
            foreach ($value as $it) {
                Wire\writeTag($buffer, $this->tag);
                $this->serializer->serialize($buffer, $it);
            }
        }
    }
}
