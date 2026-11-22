<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Benchmarks\Comparison;

use Google\Protobuf\Internal\Message;
use PhpBench\Attributes as Bench;
use Thesis\Protobuf\Benchmarks\Fixture\Item;
use Thesis\Protobuf\Benchmarks\Fixture\Items;

/**
 * 1000 nested messages, each of a number, a string and three packed numbers.
 *
 * @extends Comparison<Items, Google\Items>
 */
#[Bench\Revs(5)]
final class NestedComparison extends Comparison
{
    #[\Override]
    protected function message(): object
    {
        return new Items(array_map(
            static fn(int $i): Item => new Item($i, "item-{$i}", [$i, -$i, $i * 1000]),
            range(1, 1000),
        ));
    }

    #[\Override]
    protected function googleClass(): string
    {
        return Google\Items::class;
    }

    #[\Override]
    protected function readThesis(object $message): int
    {
        $sum = 0;

        foreach ($message->items as $item) {
            $sum += $item->id + \strlen($item->name);

            foreach ($item->values as $value) {
                $sum += $value;
            }
        }

        return $sum;
    }

    #[\Override]
    protected function readGoogle(Message $message): int
    {
        $sum = 0;

        foreach ($message->getItems() as $item) {
            $sum += $item->getId() + \strlen($item->getName());

            foreach ($item->getValues() as $value) {
                $sum += $value;
            }
        }

        return $sum;
    }
}
