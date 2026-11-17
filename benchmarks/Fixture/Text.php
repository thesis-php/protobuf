<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Benchmarks\Fixture;

use Thesis\Protobuf\Reflection;

final readonly class Text
{
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $text = '',
    ) {}
}
