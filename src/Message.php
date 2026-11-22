<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

/**
 * @api
 * @template-implements \IteratorAggregate<array-key, FieldDescriptor<*>>
 */
final readonly class Message implements
    \IteratorAggregate,
    \Countable
{
    /** @var array<positive-int, FieldDescriptor<*>> */
    public array $fields;

    /**
     * @no-named-arguments
     * @param list<FieldDescriptor<*>> $fields
     * @param list<UnknownFields\UnknownField> $unknowns
     * @throws Exception\InvalidSchema if two of the fields have the same number
     */
    public function __construct(
        array $fields = [],
        public array $unknowns = [],
    ) {
        $map = [];

        foreach ($fields as $field) {
            $map[$field->num] = $field;
        }

        // Comparing the counts costs nothing per field: a message is built for every one read and written.
        if (\count($map) !== \count($fields)) {
            $seen = [];

            foreach ($fields as $field) {
                if (isset($seen[$field->num])) {
                    throw new Exception\InvalidSchema(\sprintf(
                        'Field number %d is given more than once: the fields of a message must have distinct numbers.',
                        $field->num,
                    ));
                }

                $seen[$field->num] = true;
            }
        }

        $this->fields = $map;
    }

    public function type(): Type\MessageT
    {
        return messageT(...array_map(
            static fn(FieldDescriptor $ds) => new Type\Field(
                $ds->num,
                $ds->value->type,
            ),
            $this->fields,
        ));
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        yield from $this->fields;
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->fields);
    }
}
