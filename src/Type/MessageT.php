<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Type;

use Thesis\Protobuf\Exception\InvalidSchema;
use Thesis\Protobuf\Message;
use Thesis\Protobuf\Type;

/**
 * @template-implements Type<Message, 'repeatable', 'not-indexed'>
 * @template-implements Listable<Message>
 */
final readonly class MessageT implements
    Type,
    Listable
{
    /** @use Listed<Message> */
    use Listed;

    /** @var array<positive-int, Field<*>> */
    public array $fields;

    /**
     * @no-named-arguments
     * @param Field<*> ...$fields
     * @throws InvalidSchema if two of the fields have the same number
     */
    public function __construct(
        Field ...$fields,
    ) {
        $map = [];

        foreach ($fields as $field) {
            $map[$field->num] = $field;
        }

        // Comparing the counts costs nothing per field: Message::type() builds a type for every message value.
        if (\count($map) !== \count($fields)) {
            $seen = [];

            foreach ($fields as $field) {
                if (isset($seen[$field->num])) {
                    throw new InvalidSchema(\sprintf(
                        'Field number %d is declared more than once: the fields of a message type must have distinct numbers.',
                        $field->num,
                    ));
                }

                $seen[$field->num] = true;
            }
        }

        $this->fields = $map;
    }

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->message($this);
    }
}
