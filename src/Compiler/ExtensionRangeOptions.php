<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class ExtensionRangeOptions
{
    /**
     * @param list<UninterpretedOption> $uninterpretedOptions
     * @param list<ExtensionDeclaration> $declarations
     */
    public function __construct(
        #[Reflection\Field(999, new Reflection\ListT(
            new Reflection\ObjectT(UninterpretedOption::class),
        ))]
        public array $uninterpretedOptions = [],
        #[Reflection\Field(2, new Reflection\ListT(
            new Reflection\ObjectT(ExtensionDeclaration::class),
        ))]
        public array $declarations = [],
        #[Reflection\Field(50, new Reflection\ObjectT(FeatureSet::class))]
        public ?FeatureSet $features = null,
        #[Reflection\Field(3, new Reflection\EnumT(ExtensionVerificationState::class))]
        public ExtensionVerificationState $verification = ExtensionVerificationState::Unverified,
    ) {}
}
