<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Known;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class BoolValueKind implements ValueKind
{
    public function __construct(
        #[Reflection\Field(4, Reflection\BoolT::T)]
        public bool $value,
    ) {}
}
