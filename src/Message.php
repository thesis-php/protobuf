<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use Thesis\Protobuf\Internal\Schema\Type;

/**
 * @api
 * @template-implements \IteratorAggregate<array-key, FieldDescriptor<*>>
 */
final readonly class Message implements
    \IteratorAggregate,
    \Countable
{
    /** @var list<FieldDescriptor<*>> */
    public array $fields;

    /**
     * @no-named-arguments
     * @param FieldDescriptor<*> ...$fields
     */
    public function __construct(
        FieldDescriptor ...$fields,
    ) {
        $this->fields = $fields;
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
