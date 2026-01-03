<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Type;

use Thesis\Protobuf\Type;

/**
 * @template T
 * @template-implements Type<list<T>, 'not-repeatable', 'not-indexed', 'not-map-value'>
 */
final readonly class ListT implements Type
{
    /**
     * @param Type<T, 'repeatable', *, *> $element
     */
    public function __construct(
        public Type $element,
        public ?bool $packed = null,
    ) {}

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->list($this);
    }
}
