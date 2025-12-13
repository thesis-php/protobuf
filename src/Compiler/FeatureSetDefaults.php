<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class FeatureSetDefaults
{
    /**
     * @param list<FeatureSetDefaults\FeatureSetEditionDefault> $defaults
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\ListT(
            new Reflection\ObjectT(FeatureSetDefaults\FeatureSetEditionDefault::class),
        ))]
        public array $defaults = [],
        #[Reflection\Field(4, new Reflection\EnumT(Edition::class))]
        public ?Edition $minimumEdition = null,
        #[Reflection\Field(5, new Reflection\EnumT(Edition::class))]
        public ?Edition $maximumEdition = null,
    ) {}
}
