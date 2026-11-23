<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;
use Thesis\Protobuf\Internal\Wire;
use Thesis\Protobuf\Message;
use Thesis\Protobuf\Tag;
use Thesis\Protobuf\Type\MessageT;
use Thesis\Protobuf\WireType;

/**
 * A message field written as a group: the fields of the message between a start group tag and an end group tag of
 * the field. Occurrences of the field are merged, as those of a message field are.
 *
 * @internal
 */
final readonly class GroupCodec implements FieldCodec
{
    public function __construct(
        private MessageT $messageT,
    ) {}

    #[\Override]
    public function encode(WriteBuffer $buffer, int $num, mixed $value): void
    {
        /** @var Message $value */
        $buffer->write(Wire\tagBytes($num, WireType::SGROUP));
        MessageFields::serialize($buffer, $value);
        $buffer->write(Wire\tagBytes($num, WireType::EGROUP));
    }

    #[\Override]
    public function accepts(WireType $type): bool
    {
        return $type === WireType::SGROUP;
    }

    #[\Override]
    public function decode(ReadBuffer $buffer, Tag $tag, mixed &$slot, array &$unknowns): void
    {
        $buffer->enterNested();
        $slot = MessageFields::deserialize(
            $buffer,
            $this->messageT,
            $slot instanceof Message ? $slot : null,
            $tag->num << 3 | WireType::EGROUP->value,
        );
        $buffer->leaveNested();
    }
}
