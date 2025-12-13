<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

use Thesis\Protobuf\Compiler\FieldOptions\FeatureSupport;
use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class EnumValueOptions
{
    /**
     * @param list<UninterpretedOption> $uninterpretedOptions
     */
    public function __construct(
        #[Reflection\Field(1, Reflection\BoolT::T)]
        public bool $deprecated = false,
        #[Reflection\Field(2, new Reflection\ObjectT(FeatureSet::class))]
        public ?FeatureSet $features = null,
        #[Reflection\Field(3, Reflection\BoolT::T)]
        public bool $debugRedact = false,
        #[Reflection\Field(4, new Reflection\ObjectT(FeatureSupport::class))]
        public ?FeatureSupport $featureSupport = null,
        #[Reflection\Field(999, new Reflection\ListT(
            new Reflection\ObjectT(UninterpretedOption::class),
        ))]
        public array $uninterpretedOptions = [],
    ) {}
}
