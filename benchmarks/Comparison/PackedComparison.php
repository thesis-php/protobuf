<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Benchmarks\Comparison;

use Google\Protobuf\Internal\Message;
use PhpBench\Attributes as Bench;
use Thesis\Protobuf\Benchmarks\Fixture\PackedInts;

/**
 * A packed repeated int64 of 100 000 numbers.
 *
 * @extends Comparison<PackedInts, Google\PackedInts>
 */
#[Bench\Revs(1)]
final class PackedComparison extends Comparison
{
    #[\Override]
    protected function message(): object
    {
        return new PackedInts(array_map(
            static fn(int $i): int => $i * 7919 - 500_000,
            range(1, 100_000),
        ));
    }

    #[\Override]
    protected function googleClass(): string
    {
        return Google\PackedInts::class;
    }

    #[\Override]
    protected function readThesis(object $message): int
    {
        $sum = 0;

        foreach ($message->values as $value) {
            $sum += $value;
        }

        return $sum;
    }

    #[\Override]
    protected function readGoogle(Message $message): int
    {
        $sum = 0;

        foreach ($message->getValues() as $value) {
            $sum += $value;
        }

        return $sum;
    }
}
