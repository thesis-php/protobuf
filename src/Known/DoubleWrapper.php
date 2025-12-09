<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Known;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class DoubleWrapper
{
    public function __construct(
        #[Reflection\Field(1, Reflection\DoubleT::T)]
        public float $value,
    ) {}
}
