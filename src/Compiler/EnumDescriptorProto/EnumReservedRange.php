<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler\EnumDescriptorProto;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class EnumReservedRange
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public ?int $start = null,
        #[Reflection\Field(2, Reflection\Int32T::T)]
        public ?int $end = null,
    ) {}
}
