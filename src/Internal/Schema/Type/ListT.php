<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Schema\Type;

use Thesis\Protobuf\Internal\Schema\Type;

/**
 * @internal
 * @template T
 * @template-implements Type<list<T>>
 */
final readonly class ListT implements Type
{
    /**
     * @param Type<T> $element
     */
    public function __construct(
        public Type $element,
    ) {}

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->list($this);
    }
}
