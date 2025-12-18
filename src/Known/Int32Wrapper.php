<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Known;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class Int32Wrapper
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $value,
    ) {}
}
