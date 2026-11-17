<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Benchmarks\Fixture;

use Thesis\Protobuf\Map;
use Thesis\Protobuf\Reflection;

final readonly class StringMap
{
    /**
     * @param Map<string, int> $values
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\MapT(Reflection\StringT::T, Reflection\Int32T::T))]
        public Map $values = new Map(),
    ) {}
}
