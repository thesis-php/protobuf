<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Known;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class Uint32Wrapper
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Uint32T::T)]
        public int $value,
    ) {}
}
