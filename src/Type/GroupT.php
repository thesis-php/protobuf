<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Type;

use Thesis\Protobuf\Message;
use Thesis\Protobuf\Type;

/**
 * A message field written as a group: its fields between a start group tag and an end group tag instead of after
 * their length. It is a group field of proto2 and a field with `features.message_encoding = DELIMITED` of editions.
 * The encoding belongs to the field, not to the message: a field of the same message type may be written either way.
 *
 * @template-implements Type<Message, 'repeatable', 'not-indexed', 'not-map-value'>
 * @template-implements Listable<Message>
 */
final readonly class GroupT implements
    Type,
    Listable
{
    /** @use Listed<Message> */
    use Listed;

    public function __construct(
        public MessageT|RecursionT $message,
    ) {}

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->group($this);
    }
}
