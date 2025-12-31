<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class DescriptorProto
{
    /**
     * @param list<FieldDescriptorProto> $fields
     * @param list<FieldDescriptorProto> $extensions
     * @param list<DescriptorProto> $nestedTypes
     * @param list<EnumDescriptorProto> $enumTypes
     * @param list<DescriptorProto\ExtensionRange> $extensionRanges
     * @param list<OneofDescriptorProto> $oneofs
     * @param list<DescriptorProto\ReservedRange> $reservedRanges
     * @param list<string> $reservedNames
     */
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public ?string $name = null,
        #[Reflection\Field(2, new Reflection\ListT(
            new Reflection\ObjectT(FieldDescriptorProto::class),
        ))]
        public array $fields = [],
        #[Reflection\Field(6, new Reflection\ListT(
            new Reflection\ObjectT(FieldDescriptorProto::class),
        ))]
        public array $extensions = [],
        #[Reflection\Field(3, new Reflection\ListT(
            new Reflection\ObjectT(self::class),
        ))]
        public array $nestedTypes = [],
        #[Reflection\Field(4, new Reflection\ListT(
            new Reflection\ObjectT(EnumDescriptorProto::class),
        ))]
        public array $enumTypes = [],
        #[Reflection\Field(5, new Reflection\ListT(
            new Reflection\ObjectT(DescriptorProto\ExtensionRange::class),
        ))]
        public array $extensionRanges = [],
        #[Reflection\Field(8, new Reflection\ListT(
            new Reflection\ObjectT(OneofDescriptorProto::class),
        ))]
        public array $oneofs = [],
        #[Reflection\Field(7, new Reflection\ObjectT(MessageOptions::class))]
        public ?MessageOptions $options = null,
        #[Reflection\Field(9, new Reflection\ListT(
            new Reflection\ObjectT(DescriptorProto\ReservedRange::class),
        ))]
        public array $reservedRanges = [],
        #[Reflection\Field(10, new Reflection\ListT(Reflection\StringT::T))]
        public array $reservedNames = [],
        #[Reflection\Field(11, new Reflection\EnumT(SymbolVisibility::class))]
        public ?SymbolVisibility $visibility = null,
    ) {}
}
