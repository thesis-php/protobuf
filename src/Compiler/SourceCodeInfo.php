<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class SourceCodeInfo
{
    /**
     * @param list<SourceCodeInfo\Location> $locations
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\ListT(
            new Reflection\ObjectT(SourceCodeInfo\Location::class),
        ))]
        public array $locations,
    ) {}
}
