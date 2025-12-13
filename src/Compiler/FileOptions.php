<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class FileOptions
{
    /**
     * @param list<UninterpretedOption> $uninterpretedOptions
     */
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public ?string $javaPackage = null,
        #[Reflection\Field(8, Reflection\StringT::T)]
        public ?string $javaOuterClassname = null,
        #[Reflection\Field(10, Reflection\BoolT::T)]
        public bool $javaMultipleFiles = false,
        #[Reflection\Field(27, Reflection\BoolT::T)]
        public bool $javaStringCheckUtf8 = false,
        #[Reflection\Field(9, new Reflection\EnumT(FileOptions\OptimizeMode::class), FileOptions\OptimizeMode::SPEED)]
        public FileOptions\OptimizeMode $optimizeFor = FileOptions\OptimizeMode::SPEED,
        #[Reflection\Field(11, Reflection\StringT::T)]
        public ?string $goPackage = null,
        #[Reflection\Field(16, Reflection\BoolT::T)]
        public bool $ccGenericServices = false,
        #[Reflection\Field(17, Reflection\BoolT::T)]
        public bool $javaGenericServices = false,
        #[Reflection\Field(18, Reflection\BoolT::T)]
        public bool $pyGenericServices = false,
        #[Reflection\Field(23, Reflection\BoolT::T)]
        public bool $deprecated = false,
        #[Reflection\Field(31, Reflection\BoolT::T)]
        public bool $ccEnableArenas = true,
        #[Reflection\Field(36, Reflection\StringT::T)]
        public ?string $objcClassPrefix = null,
        #[Reflection\Field(37, Reflection\StringT::T)]
        public ?string $csharpNamespace = null,
        #[Reflection\Field(39, Reflection\StringT::T)]
        public ?string $swiftPrefix = null,
        #[Reflection\Field(40, Reflection\StringT::T)]
        public ?string $phpClassPrefix = null,
        #[Reflection\Field(41, Reflection\StringT::T)]
        public ?string $phpNamespace = null,
        #[Reflection\Field(44, Reflection\StringT::T)]
        public ?string $phpMetadataNamespace = null,
        #[Reflection\Field(45, Reflection\StringT::T)]
        public ?string $rubyPackage = null,
        #[Reflection\Field(50, new Reflection\ObjectT(FeatureSet::class))]
        public ?FeatureSet $features = null,
        #[Reflection\Field(999, new Reflection\ListT(
            new Reflection\ObjectT(UninterpretedOption::class),
        ))]
        public array $uninterpretedOptions = [],
    ) {}
}
