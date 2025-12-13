<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class UninterpretedOption
{
    /**
     * @param list<UninterpretedOption\NamePart> $name
     */
    public function __construct(
        #[Reflection\Field(2, new Reflection\ListT(
            new Reflection\ObjectT(UninterpretedOption\NamePart::class),
        ))]
        public array $name = [],
        #[Reflection\Field(3, Reflection\StringT::T)]
        public ?string $identifierValue = null,
        #[Reflection\Field(4, Reflection\Uint64T::T)]
        public ?int $positiveIntValue = null,
        #[Reflection\Field(5, Reflection\Int64T::T)]
        public ?int $negativeIntValue = null,
        #[Reflection\Field(6, Reflection\DoubleT::T)]
        public ?float $doubleValue = null,
        #[Reflection\Field(7, Reflection\BytesT::T)]
        public ?string $stringValue = null,
        #[Reflection\Field(8, Reflection\StringT::T)]
        public ?string $aggregateValue = null,
    ) {}
}
