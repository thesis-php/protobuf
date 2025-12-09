<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Known;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class FieldDefinition
{
    /**
     * @param list<Option> $options
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\EnumT(FieldKind::class))]
        public FieldKind $kind,
        #[Reflection\Field(2, new Reflection\EnumT(FieldCardinality::class))]
        public FieldCardinality $cardinality,
        #[Reflection\Field(3, Reflection\Int32T::T)]
        public int $number,
        #[Reflection\Field(4, Reflection\StringT::T)]
        public string $name,
        #[Reflection\Field(6, Reflection\StringT::T)]
        public ?string $typeUrl = null,
        #[Reflection\Field(7, Reflection\Int32T::T)]
        public ?int $oneOfIndex = null,
        #[Reflection\Field(8, Reflection\BoolT::T)]
        public bool $packed = false,
        #[Reflection\Field(9, new Reflection\ListT(
            new Reflection\ObjectT(Option::class),
        ))]
        public array $options = [],
        #[Reflection\Field(10, Reflection\StringT::T)]
        public ?string $jsonName = null,
        #[Reflection\Field(11, Reflection\StringT::T)]
        public ?string $defaultValue = null,
    ) {}
}
