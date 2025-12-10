<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class ExtensionDeclaration
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public ?int $number = null,
        #[Reflection\Field(2, Reflection\StringT::T)]
        public ?string $fullName = null,
        #[Reflection\Field(3, Reflection\StringT::T)]
        public ?string $type = null,
        #[Reflection\Field(5, Reflection\BoolT::T)]
        public ?bool $reserved = null,
        #[Reflection\Field(6, Reflection\BoolT::T)]
        public ?bool $repeated = null,
    ) {}
}
