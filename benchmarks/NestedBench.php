<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Benchmarks;

use PhpBench\Attributes as Bench;
use Thesis\Protobuf\Benchmarks\Fixture\Item;
use Thesis\Protobuf\Benchmarks\Fixture\Items;

/**
 * A list of nested messages: two sizes to show how the cost grows with the input.
 *
 * @extends CodecBench<array{count: positive-int}>
 */
#[Bench\Revs(5)]
#[Bench\ParamProviders('provideCounts')]
final class NestedBench extends CodecBench
{
    /**
     * @return iterable<string, array{count: positive-int}>
     */
    public function provideCounts(): iterable
    {
        yield 'x1000' => ['count' => 1000];
        yield 'x4000' => ['count' => 4000];
    }

    #[\Override]
    protected function message(array $params): object
    {
        return new Items(array_map(
            static fn(int $i): Item => new Item($i, "item-{$i}", [$i, -$i, $i * 1000]),
            range(1, $params['count']),
        ));
    }
}
