<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class MethodDescriptorProto
{
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public ?string $name = null,
        #[Reflection\Field(2, Reflection\StringT::T)]
        public ?string $inputType = null,
        #[Reflection\Field(3, Reflection\StringT::T)]
        public ?string $outputType = null,
        #[Reflection\Field(4, new Reflection\ObjectT(MethodOptions::class))]
        public ?MethodOptions $options = null,
        #[Reflection\Field(5, Reflection\BoolT::T)]
        public bool $clientStreaming = false,
        #[Reflection\Field(6, Reflection\BoolT::T)]
        public bool $serverStreaming = false,
    ) {}
}
