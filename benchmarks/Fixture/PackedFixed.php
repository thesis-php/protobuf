<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Benchmarks\Fixture;

use BcMath\Number;
use Thesis\Protobuf\Reflection;

final readonly class PackedFixed
{
    /**
     * @param list<Number> $ids
     * @param list<float> $values
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\ListT(Reflection\Fixed64T::T))]
        public array $ids = [],
        #[Reflection\Field(2, new Reflection\ListT(Reflection\DoubleT::T))]
        public array $values = [],
    ) {}
}
