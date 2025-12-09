<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Known;

use BcMath\Number;
use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class Int64Wrapper
{
    /**
     * @param Number|int|numeric-string $value
     */
    public function __construct(
        #[Reflection\Field(1, Reflection\Int64T::T)]
        public Number|int|string $value,
    ) {}
}
