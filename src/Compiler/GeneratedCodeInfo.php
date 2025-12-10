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
     * @param list<Annotation> $annotations
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\ListT(
            new Reflection\ObjectT(Annotation::class),
        ))]
        public array $annotations,
    ) {}
}
