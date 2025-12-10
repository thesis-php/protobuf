<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class EnumValueDescriptorProto
{
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public ?string $name = null,
        #[Reflection\Field(2, Reflection\Int32T::T)]
        public ?int $number = null,
        #[Reflection\Field(3, new Reflection\ObjectT(EnumValueOptions::class))]
        public ?EnumValueOptions $options = null,
    ) {}
}
