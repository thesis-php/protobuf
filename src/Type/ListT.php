<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Type;

use Thesis\Protobuf\Exception\InvalidSchema;
use Thesis\Protobuf\Type;
use Thesis\Protobuf\Type\Visitor\IsPackable;

/**
 * @template T
 * @template-implements Type<list<T>, 'not-repeatable', 'not-indexed', 'not-map-value'>
 */
final readonly class ListT implements Type
{
    /**
     * @param Type<T, 'repeatable', *, *> $element
     * @throws InvalidSchema if a non-packable element type is declared packed
     */
    public function __construct(
        public Type $element,
        public ?bool $packed = null,
    ) {
        // A recursive message is not resolved here: the type it refers to may still be under construction.
        if ($packed === true && ($element instanceof RecursionT || !$element->accept(new IsPackable()))) {
            throw new InvalidSchema(\sprintf(
                'Repeated fields of type "%s" cannot be packed: only scalar numeric, bool and enum elements can.',
                $element::class,
            ));
        }
    }

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->list($this);
    }
}
