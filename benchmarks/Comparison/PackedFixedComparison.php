<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Benchmarks\Comparison;

use BcMath\Number;
use Google\Protobuf\Internal\Message;
use PhpBench\Attributes as Bench;
use Thesis\Protobuf\Benchmarks\Fixture\PackedFixed;

/**
 * A packed repeated fixed64 of 100 000 numbers and a packed repeated double of as many.
 *
 * @extends Comparison<PackedFixed, Google\PackedFixed>
 */
#[Bench\Revs(1)]
final class PackedFixedComparison extends Comparison
{
    #[\Override]
    protected function message(): object
    {
        $numbers = range(1, 100_000);

        return new PackedFixed(
            array_map(static fn(int $i): Number => new Number($i * 7919), $numbers),
            array_map(static fn(int $i): float => $i * 0.25 - 1000, $numbers),
        );
    }

    #[\Override]
    protected function googleClass(): string
    {
        return Google\PackedFixed::class;
    }

    #[\Override]
    protected function readThesis(object $message): int
    {
        $sum = 0;

        foreach ($message->ids as $id) {
            $sum += (int) $id->value;
        }

        $total = 0.0;

        foreach ($message->values as $value) {
            $total += $value;
        }

        return $sum + (int) $total;
    }

    #[\Override]
    protected function readGoogle(Message $message): int
    {
        $sum = 0;

        foreach ($message->getIds() as $id) {
            $sum += $id;
        }

        $total = 0.0;

        foreach ($message->getValues() as $value) {
            $total += $value;
        }

        return $sum + (int) $total;
    }
}
