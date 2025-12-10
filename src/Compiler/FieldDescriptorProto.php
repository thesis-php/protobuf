<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class FieldDescriptorProto
{
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public ?string $name = null,
        #[Reflection\Field(3, Reflection\Int32T::T)]
        public ?int $number = null,
        #[Reflection\Field(4, new Reflection\EnumT(FieldDescriptorProtoLabel::class))]
        public ?FieldDescriptorProtoLabel $label = null,
        #[Reflection\Field(5, new Reflection\EnumT(FieldDescriptorProtoType::class))]
        public ?FieldDescriptorProtoType $type = null,
        #[Reflection\Field(6, Reflection\StringT::T)]
        public ?string $typeName = null,
        #[Reflection\Field(2, Reflection\StringT::T)]
        public ?string $extendee  = null,
        #[Reflection\Field(7, Reflection\StringT::T)]
        public ?string $defaultValue = null,
        #[Reflection\Field(9, Reflection\Int32T::T)]
        public ?int $oneofIndex = null,
        #[Reflection\Field(10, Reflection\StringT::T)]
        public ?string $jsonName = null,
        #[Reflection\Field(8, new Reflection\ObjectT(FieldOptions::class))]
        public ?FieldOptions $options = null,
        #[Reflection\Field(17, Reflection\BoolT::T)]
        public ?bool $proto3Optional = null,
    ) {}
}
