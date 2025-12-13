<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler\DescriptorProto;

use Thesis\Protobuf\Compiler\ExtensionRangeOptions;
use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class ExtensionRange
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public ?int $start = null,
        #[Reflection\Field(2, Reflection\Int32T::T)]
        public ?int $end = null,
        #[Reflection\Field(3, new Reflection\ObjectT(ExtensionRangeOptions::class))]
        public ?ExtensionRangeOptions $options = null,
    ) {}
}
