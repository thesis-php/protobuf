<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class ServiceDescriptorProto
{
    /**
     * @param list<MethodDescriptorProto> $methods
     */
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public ?string $name = null,
        #[Reflection\Field(2, new Reflection\ListT(
            new Reflection\ObjectT(MethodDescriptorProto::class),
        ))]
        public array $methods = [],
        #[Reflection\Field(3, new Reflection\ObjectT(ServiceOptions::class))]
        public ?ServiceOptions $options = null,
    ) {}
}
