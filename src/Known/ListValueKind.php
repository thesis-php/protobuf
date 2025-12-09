<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Known;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class ListValueKind implements ValueKind
{
    public function __construct(
        #[Reflection\Field(6, new Reflection\ObjectT(ListValue::class))]
        public ListValue $value,
    ) {}
}
