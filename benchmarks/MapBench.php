<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Benchmarks;

use PhpBench\Attributes as Bench;
use Thesis\Protobuf\Benchmarks\Fixture\StringMap;
use Thesis\Protobuf\Map;

/**
 * @extends CodecBench<array{count: positive-int}>
 */
#[Bench\Revs(1)]
#[Bench\ParamProviders('provideCounts')]
final class MapBench extends CodecBench
{
    /**
     * @return iterable<string, array{count: positive-int}>
     */
    public function provideCounts(): iterable
    {
        yield 'x10k' => ['count' => 10_000];
    }

    #[\Override]
    protected function message(array $params): object
    {
        /** @var Map<string, int> $map */
        $map = new Map();

        for ($i = 0; $i < $params['count']; ++$i) {
            $map["key-{$i}"] = $i;
        }

        return new StringMap($map);
    }
}
