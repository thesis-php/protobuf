<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler\FieldOptions;

use Thesis\Protobuf\Compiler\Edition;
use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class EditionDefault
{
    public function __construct(
        #[Reflection\Field(3, new Reflection\EnumT(Edition::class))]
        public ?Edition $edition = null,
        #[Reflection\Field(2, Reflection\StringT::T)]
        public ?string $value = null,
    ) {}
}
