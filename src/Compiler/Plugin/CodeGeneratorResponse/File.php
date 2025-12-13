<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler\Plugin\CodeGeneratorResponse;

use Thesis\Protobuf\Compiler\GeneratedCodeInfo;
use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class File
{
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public ?string $name = null,
        #[Reflection\Field(2, Reflection\StringT::T)]
        public ?string $insertionPoint = null,
        #[Reflection\Field(15, Reflection\StringT::T)]
        public ?string $content = null,
        #[Reflection\Field(16, new Reflection\ObjectT(GeneratedCodeInfo::class))]
        public ?GeneratedCodeInfo $generatedCodeInfo = null,
    ) {}
}
