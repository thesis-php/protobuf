<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Known;

use Thesis\Protobuf\Reflection;

/**
 * @api
 * @see https://github.com/protocolbuffers/protobuf/blob/main/src/google/protobuf/api.proto
 */
final readonly class Api
{
    /**
     * @param list<Method> $methods
     * @param list<Option> $options
     * @param list<Mixin> $mixins
     */
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $name,
        #[Reflection\Field(2, new Reflection\ListT(
            new Reflection\ObjectT(Method::class),
        ))]
        public array $methods,
        #[Reflection\Field(3, new Reflection\ListT(
            new Reflection\ObjectT(Option::class),
        ))]
        public array $options,
        #[Reflection\Field(4, Reflection\StringT::T)]
        public ?string $version = null,
        #[Reflection\Field(5, new Reflection\ObjectT(SourceContext::class))]
        public ?SourceContext $context = null,
        #[Reflection\Field(6, new Reflection\ListT(
            new Reflection\ObjectT(Mixin::class),
        ))]
        public array $mixins = [],
        #[Reflection\Field(7, new Reflection\EnumT(Syntax::class))]
        public ?Syntax $syntax = null,
        #[Reflection\Field(8, Reflection\StringT::T)]
        public ?string $edition = null,
    ) {}
}
