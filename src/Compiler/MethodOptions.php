<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class MethodOptions
{
    /**
     * @param list<UninterpretedOption> $uninterpretedOptions
     */
    public function __construct(
        #[Reflection\Field(33, Reflection\BoolT::T)]
        public bool $deprecated = false,
        #[Reflection\Field(34, new Reflection\EnumT(MethodOptions\IdempotencyLevel::class))]
        public MethodOptions\IdempotencyLevel $idempotencyLevel = MethodOptions\IdempotencyLevel::IDEMPOTENCY_UNKNOWN,
        #[Reflection\Field(35, new Reflection\ObjectT(FeatureSet::class))]
        public ?FeatureSet $featureSet = null,
        #[Reflection\Field(999, new Reflection\ListT(
            new Reflection\ObjectT(UninterpretedOption::class),
        ))]
        public array $uninterpretedOptions = [],
    ) {}
}
