<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class Location
{
    /**
     * @param list<int> $path
     * @param list<int> $span
     * @param list<string> $leadingDetachedComments
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\ListT(Reflection\Int32T::T))]
        public array $path,
        #[Reflection\Field(2, new Reflection\ListT(Reflection\Int32T::T))]
        public array $span,
        #[Reflection\Field(3, Reflection\StringT::T)]
        public ?string $leadingComments = null,
        #[Reflection\Field(4, Reflection\StringT::T)]
        public ?string $trailingComments = null,
        #[Reflection\Field(6, new Reflection\ListT(Reflection\StringT::T))]
        public array $leadingDetachedComments = [],
    ) {}
}
