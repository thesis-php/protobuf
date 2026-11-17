<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Benchmarks\Fixture;

use Thesis\Protobuf\Reflection;

final readonly class Item
{
    /**
     * @param list<int> $values
     */
    public function __construct(
        #[Reflection\Field(1, Reflection\Uint32T::T)]
        public int $id = 0,
        #[Reflection\Field(2, Reflection\StringT::T)]
        public string $name = '',
        #[Reflection\Field(3, new Reflection\ListT(Reflection\Int64T::T))]
        public array $values = [],
    ) {}
}
