<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Benchmarks\Comparison;

use Google\Protobuf\Internal\Message;
use PhpBench\Attributes as Bench;
use Thesis\Protobuf\Benchmarks\Fixture\Small;

/**
 * @extends Comparison<Small, Google\Small>
 */
#[Bench\Revs(10000)]
final class SmallComparison extends Comparison
{
    #[\Override]
    protected function message(): object
    {
        return new Small('John Doe', 50, true);
    }

    #[\Override]
    protected function googleClass(): string
    {
        return Google\Small::class;
    }

    #[\Override]
    protected function readThesis(object $message): int
    {
        return \strlen($message->name) + $message->id + (int) $message->active;
    }

    #[\Override]
    protected function readGoogle(Message $message): int
    {
        return \strlen($message->getName()) + $message->getId() + (int) $message->getActive();
    }
}
