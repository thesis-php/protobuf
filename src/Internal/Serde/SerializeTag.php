<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Protobuf\Internal\Buffer\WriteBuffer;
use Thesis\Protobuf\Internal\Wire;
use Thesis\Protobuf\Tag;

/**
 * @internal
 * @template T
 * @template-implements SerializeValue<T>
 */
final readonly class SerializeTag implements SerializeValue
{
    public function __construct(
        private Tag $tag,
    ) {}

    #[\Override]
    public function serialize(WriteBuffer $buffer, mixed $value): void
    {
        Wire\writeTag($buffer, $this->tag);
    }
}
