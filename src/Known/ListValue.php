<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Known;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class ListValue
{
    /**
     * @param list<Value> $values
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\ListT(
            new Reflection\ObjectT(Value::class),
        ))]
        public array $values,
    ) {}
}
