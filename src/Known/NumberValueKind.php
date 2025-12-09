<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Known;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class NumberValueKind implements ValueKind
{
    public function __construct(
        #[Reflection\Field(2, Reflection\DoubleT::T)]
        public float $value,
    ) {}
}
