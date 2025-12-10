<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class MessageOptions
{
    /**
     * @param list<UninterpretedOption> $uninterpretedOptions
     */
    public function __construct(
        #[Reflection\Field(1, Reflection\BoolT::T)]
        public bool $messageSetWireFormat = false,
        #[Reflection\Field(2, Reflection\BoolT::T)]
        public bool $noStandardDescriptorAccessor = false,
        #[Reflection\Field(3, Reflection\BoolT::T)]
        public bool $deprecated = false,
        #[Reflection\Field(7, Reflection\BoolT::T)]
        public ?bool $mapEntry = null,
        #[Reflection\Field(11, Reflection\BoolT::T)]
        public ?bool $deprecatedLegacyJsonFieldConflicts = null,
        #[Reflection\Field(12, new Reflection\ObjectT(FeatureSet::class))]
        public ?FeatureSet $features = null,
        #[Reflection\Field(999, new Reflection\ListT(
            new Reflection\ObjectT(UninterpretedOption::class),
        ))]
        public array $uninterpretedOptions = [],
    ) {}
}
