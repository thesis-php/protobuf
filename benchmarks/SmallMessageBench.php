<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Benchmarks;

use PhpBench\Attributes as Bench;
use Thesis\Protobuf\Benchmarks\Fixture\Small;

/**
 * @extends CodecBench<array{}>
 */
#[Bench\Revs(1000)]
final class SmallMessageBench extends CodecBench
{
    #[\Override]
    protected function message(array $params): object
    {
        return new Small('John Doe', 50, true);
    }
}
