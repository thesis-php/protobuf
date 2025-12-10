<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class FieldOptions
{
    /**
     * @param list<OptionTargetType> $targets
     * @param list<EditionDefault> $editionDefaults
     * @param list<UninterpretedOption> $uninterpretedOptions
     */
    public function __construct(
        #[Reflection\Field(2, Reflection\BoolT::T)]
        public ?bool $packed = null,
        #[Reflection\Field(6, new Reflection\EnumT(JSType::class))]
        public JSType $jsType = JSType::Normal,
        #[Reflection\Field(5, Reflection\BoolT::T)]
        public bool $lazy = false,
        #[Reflection\Field(15, Reflection\BoolT::T)]
        public bool $unverifiedLazy = false,
        #[Reflection\Field(3, Reflection\BoolT::T)]
        public bool $deprecated = false,
        #[Reflection\Field(16, Reflection\BoolT::T)]
        public bool $debugRedact = false,
        #[Reflection\Field(17, new Reflection\EnumT(OptionRetention::class))]
        public ?OptionRetention $retention = null,
        #[Reflection\Field(19, new Reflection\ListT(
            new Reflection\ObjectT(OptionTargetType::class),
        ))]
        public array $targets = [],
        #[Reflection\Field(20, new Reflection\ListT(
            new Reflection\ObjectT(EditionDefault::class),
        ))]
        public array $editionDefaults = [],
        #[Reflection\Field(21, new Reflection\ObjectT(FeatureSet::class))]
        public ?FeatureSet $features = null,
        #[Reflection\Field(22, new Reflection\ObjectT(FeatureSupport::class))]
        public ?FeatureSupport $featureSupport = null,
        #[Reflection\Field(999, new Reflection\ListT(
            new Reflection\ObjectT(UninterpretedOption::class),
        ))]
        public array $uninterpretedOptions = [],
    ) {}
}
