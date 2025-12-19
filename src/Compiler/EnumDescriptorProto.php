<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class EnumDescriptorProto
{
    /**
     * @param list<EnumValueDescriptorProto> $values
     * @param list<EnumDescriptorProto\EnumReservedRange> $reservedRanges
     * @param list<string> $reservedNames
     */
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $name,
        #[Reflection\Field(2, new Reflection\ListT(
            new Reflection\ObjectT(EnumValueDescriptorProto::class),
        ))]
        public array $values = [],
        #[Reflection\Field(3, new Reflection\ObjectT(EnumOptions::class))]
        public ?EnumOptions $options = null,
        #[Reflection\Field(4, new Reflection\ListT(
            new Reflection\ObjectT(EnumDescriptorProto\EnumReservedRange::class),
        ))]
        public array $reservedRanges = [],
        #[Reflection\Field(5, new Reflection\ListT(Reflection\StringT::T))]
        public array $reservedNames = [],
        #[Reflection\Field(6, new Reflection\EnumT(SymbolVisibility::class))]
        public ?SymbolVisibility $visibility = null,
    ) {}
}
