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
     */
    public function __construct(
        array $fields = [],
        public array $unknowns = [],
    ) {
        $map = [];

        foreach ($fields as $field) {
            $map[$field->num] = $field;
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
