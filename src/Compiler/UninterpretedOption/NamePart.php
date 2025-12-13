<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler\UninterpretedOption;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class NamePart
{
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $namePart,
        #[Reflection\Field(2, Reflection\BoolT::T)]
        public bool $isExtension,
    ) {}
}
