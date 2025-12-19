<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

use BcMath\Number;
use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class FileDescriptorProto
{
    /**
     * @param list<string> $dependencies
     * @param list<Number> $publicDependencies
     * @param list<Number> $weakDependencies
     * @param list<string> $optionDependencies
     * @param list<DescriptorProto> $messages
     * @param list<EnumDescriptorProto> $enums
     * @param list<ServiceDescriptorProto> $services
     * @param list<FieldDescriptorProto> $extensions
     */
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $name,
        #[Reflection\Field(2, Reflection\StringT::T)]
        public string $package,
        #[Reflection\Field(3, new Reflection\ListT(Reflection\StringT::T))]
        public array $dependencies = [],
        #[Reflection\Field(10, new Reflection\ListT(Reflection\Int32T::T))]
        public array $publicDependencies = [],
        #[Reflection\Field(11, new Reflection\ListT(Reflection\Int32T::T))]
        public array $weakDependencies = [],
        #[Reflection\Field(15, new Reflection\ListT(Reflection\StringT::T))]
        public array $optionDependencies = [],
        #[Reflection\Field(4, new Reflection\ListT(
            new Reflection\ObjectT(DescriptorProto::class),
        ))]
        public array $messages = [],
        #[Reflection\Field(5, new Reflection\ListT(
            new Reflection\ObjectT(EnumDescriptorProto::class),
        ))]
        public array $enums = [],
        #[Reflection\Field(6, new Reflection\ListT(
            new Reflection\ObjectT(ServiceDescriptorProto::class),
        ))]
        public array $services = [],
        #[Reflection\Field(7, new Reflection\ListT(
            new Reflection\ObjectT(FieldDescriptorProto::class),
        ))]
        public array $extensions = [],
        #[Reflection\Field(8, new Reflection\ObjectT(FileOptions::class))]
        public ?FileOptions $options = null,
        #[Reflection\Field(9, new Reflection\ObjectT(SourceCodeInfo::class))]
        public ?SourceCodeInfo $sourceCodeInfo = null,
        #[Reflection\Field(12, Reflection\StringT::T)]
        public ?string $syntax = null,
        #[Reflection\Field(14, new Reflection\EnumT(Edition::class))]
        public ?Edition $edition = null,
    ) {}
}
