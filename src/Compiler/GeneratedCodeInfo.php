<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final class GeneratedCodeInfo
{
    /**
     * @param list<GeneratedCodeInfo\Annotation> $annotations
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\ListT(
            new Reflection\ObjectT(GeneratedCodeInfo\Annotation::class),
        ))]
        public array $annotations,
    ) {}
}
