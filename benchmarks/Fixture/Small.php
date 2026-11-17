<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Benchmarks\Fixture;

use Thesis\Protobuf\Reflection;

final readonly class Small
{
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $name = '',
        #[Reflection\Field(2, Reflection\Int32T::T)]
        public int $id = 0,
        #[Reflection\Field(3, Reflection\BoolT::T)]
        public bool $active = false,
    ) {}
}
