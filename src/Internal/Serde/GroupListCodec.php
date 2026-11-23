<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;
use Thesis\Protobuf\Tag;
use Thesis\Protobuf\WireType;

/**
 * A repeated field of groups: a group per element, each between its own tags.
 *
 * @internal
 */
final readonly class GroupListCodec implements FieldCodec
{
    public function __construct(
        private GroupCodec $element,
    ) {}

    #[\Override]
    public function encode(WriteBuffer $buffer, int $num, mixed $value): void
    {
        /** @var list<mixed> $value */
        foreach ($value as $it) {
            $this->element->encode($buffer, $num, $it);
        }
    }

    #[\Override]
    public function accepts(WireType $type): bool
    {
        return $this->element->accepts($type);
    }

    #[\Override]
    public function decode(ReadBuffer $buffer, Tag $tag, mixed &$slot, array &$unknowns): void
    {
        /** @var ?list<mixed> $slot */
        $slot ??= [];

        // An element is not merged into the one before it.
        $element = null;
        $this->element->decode($buffer, $tag, $element, $unknowns);
        $slot[] = $element;
    }
}
