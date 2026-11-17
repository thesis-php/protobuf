<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Benchmarks;

use PhpBench\Attributes as Bench;
use Thesis\Protobuf\Benchmarks\Fixture\PackedInts;

/**
 * A packed repeated int64: two sizes to show how the cost grows with the input.
 *
 * @extends CodecBench<array{count: positive-int}>
 */
#[Bench\Revs(1)]
#[Bench\ParamProviders('provideCounts')]
final class PackedBench extends CodecBench
{
    /**
     * @return iterable<string, array{count: positive-int}>
     */
    public function provideCounts(): iterable
    {
        yield 'x10k' => ['count' => 10_000];
        yield 'x100k' => ['count' => 100_000];
    }

    #[\Override]
    protected function message(array $params): object
    {
        return new PackedInts(array_map(
            static fn(int $i): int => $i * 7919 - 500_000,
            range(1, $params['count']),
        ));
    }
}
