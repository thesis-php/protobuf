<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Known;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class StructValueKind implements ValueKind
{
    public function __construct(
        #[Reflection\Field(5, new Reflection\ObjectT(Struct::class))]
        public Struct $struct,
    ) {}
}
