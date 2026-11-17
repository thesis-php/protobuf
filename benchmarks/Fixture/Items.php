<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Benchmarks\Fixture;

use Thesis\Protobuf\Reflection;

final readonly class Items
{
    /**
     * @param list<Item> $items
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\ListT(new Reflection\ObjectT(Item::class)))]
        public array $items = [],
    ) {}
}
