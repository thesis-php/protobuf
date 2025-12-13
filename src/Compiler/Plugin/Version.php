<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler\Plugin;

use BcMath\Number;
use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class Version
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public ?Number $major = null,
        #[Reflection\Field(2, Reflection\Int32T::T)]
        public ?Number $minor = null,
        #[Reflection\Field(3, Reflection\Int32T::T)]
        public ?Number $patch = null,
        #[Reflection\Field(4, Reflection\StringT::T)]
        public ?string $suffix = null,
    ) {}
}
