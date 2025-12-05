<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Schema\Type;

use Thesis\Protobuf\Internal\Schema\Type;
use Thesis\Protobuf\Message;

/**
 * @internal
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
     */
    public function __construct(
        Field ...$fields,
    ) {
        $map = [];

        foreach ($fields as $field) {
            $map[$field->num] = $field;
        }

        $this->fields = $map;
    }

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->message($this);
    }
}
