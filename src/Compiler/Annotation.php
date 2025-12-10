<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class Annotation
{
    /**
     * @param list<int> $path
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\ListT(Reflection\Int32T::T))]
        public array $path,
        #[Reflection\Field(2, Reflection\StringT::T)]
        public string $sourceFile,
        #[Reflection\Field(3, Reflection\Int32T::T)]
        public ?int $begin = null,
        #[Reflection\Field(4, Reflection\Int32T::T)]
        public ?int $end = null,
        #[Reflection\Field(5, new Reflection\EnumT(AnnotationSemantic::class))]
        public ?AnnotationSemantic $semantic = null,
    ) {}
}
