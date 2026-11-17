<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Benchmarks\Fixture;

use Thesis\Protobuf\Reflection;

final readonly class PackedInts
{
    /**
     * @param list<int> $values
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\ListT(Reflection\Int64T::T))]
        public array $values = [],
    ) {}
}
