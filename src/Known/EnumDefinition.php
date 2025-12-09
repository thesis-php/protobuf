<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Known;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class EnumDefinition
{
    /**
     * @param list<EnumCase> $cases
     * @param list<Option> $options
     */
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $name,
        #[Reflection\Field(2, new Reflection\ListT(
            new Reflection\ObjectT(EnumCase::class),
        ))]
        public array $cases,
        #[Reflection\Field(3, new Reflection\ListT(
            new Reflection\ObjectT(Option::class),
        ))]
        public array $options,
        #[Reflection\Field(4, new Reflection\ObjectT(SourceContext::class))]
        public SourceContext $context,
        #[Reflection\Field(5, new Reflection\EnumT(Syntax::class))]
        public Syntax $syntax,
        #[Reflection\Field(6, Reflection\StringT::T)]
        public ?string $edition = null,
    ) {}
}
