<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Protobuf\Internal\Buffer;
use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Wire;
use Thesis\Protobuf\Internal\Wire\Tag;

/**
 * @internal
 * @template T
 * @template-implements DeserializeValue<list<T>>
 */
final readonly class DeserializeList implements DeserializeValue
{
    /**
     * @param DeserializeValue<T> $deserializer
     */
    public function __construct(
        private DeserializeValue $deserializer,
        private Tag $tag,
        private bool $isPacked = false,
    ) {}

    #[\Override]
    public function deserialize(ReadBuffer $buffer): array
    {
        $values = [];

        if ($this->isPacked) {
            $buffer = Buffer\slice($buffer);

            while (\count($buffer) > 0) {
                $values[] = $this->deserializer->deserialize($buffer);
            }
        } else {
            $values[] = $this->deserializer->deserialize($buffer);

            while (\count($buffer) > 0 && $this->tag->equal(Wire\peekTag($buffer))) {
                Wire\readTag($buffer);
                $values[] = $this->deserializer->deserialize($buffer);
            }
        }

        return $values;
    }
}
