<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Known;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class StringValueKind implements ValueKind
{
    public function __construct(
        #[Reflection\Field(3, Reflection\StringT::T)]
        public string $value,
    ) {}
}
