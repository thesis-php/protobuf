<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Benchmarks;

use BcMath\Number;
use PhpBench\Attributes as Bench;
use Thesis\Protobuf\Benchmarks\Fixture\PackedFixed;

/**
 * A packed repeated fixed64 and a packed repeated double of the same length: two sizes to show how the cost grows
 * with the input.
 *
 * @extends CodecBench<array{count: positive-int}>
 */
#[Bench\Revs(1)]
#[Bench\ParamProviders('provideCounts')]
final class PackedFixedBench extends CodecBench
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
        $numbers = range(1, $params['count']);

        return new PackedFixed(
            array_map(static fn(int $i): Number => new Number($i * 7919), $numbers),
            array_map(static fn(int $i): float => $i * 0.25 - 1000, $numbers),
        );
    }
}
