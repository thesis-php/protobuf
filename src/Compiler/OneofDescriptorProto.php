<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class OneofDescriptorProto
{
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public ?string $name = null,
        #[Reflection\Field(2, new Reflection\ObjectT(OneofOptions::class))]
        public ?OneofOptions $options = null,
    ) {}
}
