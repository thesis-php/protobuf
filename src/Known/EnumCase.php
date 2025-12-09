<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Known;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class EnumCase
{
    /**
     * @param list<Option> $options
     */
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $name,
        #[Reflection\Field(2, Reflection\Int32T::T)]
        public int $number,
        #[Reflection\Field(3, new Reflection\ListT(
            new Reflection\ObjectT(Option::class),
        ))]
        public array $options = [],
    ) {}
}
