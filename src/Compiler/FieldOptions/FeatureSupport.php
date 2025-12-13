<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler\FieldOptions;

use Thesis\Protobuf\Compiler\Edition;
use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class FeatureSupport
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\EnumT(Edition::class))]
        public ?Edition $editionIntroduced = null,
        #[Reflection\Field(2, new Reflection\EnumT(Edition::class))]
        public ?Edition $editionDeprecated = null,
        #[Reflection\Field(3, Reflection\StringT::T)]
        public ?string $deprecationWarning = null,
        #[Reflection\Field(4, new Reflection\EnumT(Edition::class))]
        public ?Edition $editionRemoved = null,
        #[Reflection\Field(5, Reflection\StringT::T)]
        public ?string $removalError = null,
    ) {}
}
