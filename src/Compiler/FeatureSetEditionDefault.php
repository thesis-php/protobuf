<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class FeatureSetEditionDefault
{
    public function __construct(
        #[Reflection\Field(3, new Reflection\EnumT(Edition::class))]
        public ?Edition $edition = null,
        #[Reflection\Field(4, new Reflection\ObjectT(FeatureSet::class))]
        public ?FeatureSet $overridableFeatures = null,
        #[Reflection\Field(5, new Reflection\ObjectT(FeatureSet::class))]
        public ?FeatureSet $fixedFeatures = null,
    ) {}
}
