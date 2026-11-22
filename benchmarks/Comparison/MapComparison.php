<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Benchmarks\Comparison;

use Google\Protobuf\Internal\Message;
use PhpBench\Attributes as Bench;
use Thesis\Protobuf\Benchmarks\Fixture\StringMap;
use Thesis\Protobuf\Map;

/**
 * A map<string, int32> of 10 000 entries.
 *
 * @extends Comparison<StringMap, Google\StringMap>
 */
#[Bench\Revs(1)]
final class MapComparison extends Comparison
{
    #[\Override]
    protected function message(): object
    {
        /** @var Map<string, int> $map */
        $map = new Map();

        for ($i = 0; $i < 10_000; ++$i) {
            $map["key-{$i}"] = $i;
        }

        return new StringMap($map);
    }

    #[\Override]
    protected function googleClass(): string
    {
        return Google\StringMap::class;
    }

    #[\Override]
    protected function readThesis(object $message): int
    {
        $sum = 0;

        foreach ($message->values as $key => $value) {
            $sum += \strlen($key) + $value;
        }

        return $sum;
    }

    #[\Override]
    protected function readGoogle(Message $message): int
    {
        $sum = 0;

        foreach ($message->getValues() as $key => $value) {
            $sum += \strlen($key) + $value;
        }

        return $sum;
    }
}
