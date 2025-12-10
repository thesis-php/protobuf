<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class UninterpretedOptionNamePart
{
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $namePart,
        #[Reflection\Field(2, Reflection\BoolT::T)]
        public bool $isExtension,
    ) {}
}
