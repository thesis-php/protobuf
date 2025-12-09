<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Known;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class Method
{
    /**
     * @param list<Option> $options
     */
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $name,
        #[Reflection\Field(2, Reflection\StringT::T)]
        public string $requestTypeUrl,
        #[Reflection\Field(3, Reflection\BoolT::T)]
        public bool $requestStreaming,
        #[Reflection\Field(4, Reflection\StringT::T)]
        public string $responseTypeUrl,
        #[Reflection\Field(5, Reflection\BoolT::T)]
        public bool $responseStreaming,
        #[Reflection\Field(6, new Reflection\ListT(
            new Reflection\ObjectT(Option::class),
        ))]
        public array $options = [],
        #[Reflection\Field(7, new Reflection\EnumT(Syntax::class))]
        public Syntax $syntax = Syntax::PROTO3,
        #[Reflection\Field(8, Reflection\StringT::T)]
        public ?string $edition = null,
    ) {}
}
