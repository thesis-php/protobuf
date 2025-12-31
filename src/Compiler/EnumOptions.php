<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class EnumOptions
{
    /**
     * @param list<UninterpretedOption> $uninterpretedOptions
     */
    public function __construct(
        #[Reflection\Field(2, Reflection\BoolT::T)]
        public ?bool $allowAlias = null,
        #[Reflection\Field(3, Reflection\BoolT::T)]
        public bool $deprecated = false,
        #[Reflection\Field(6, Reflection\BoolT::T)]
        public bool $deprecatedLegacyJsonFieldConflicts = true,
        #[Reflection\Field(7, new Reflection\ObjectT(FeatureSet::class))]
        public ?FeatureSet $features = null,
        #[Reflection\Field(999, new Reflection\ListT(
            new Reflection\ObjectT(UninterpretedOption::class),
        ))]
        public array $uninterpretedOptions = [],
    ) {}
}
