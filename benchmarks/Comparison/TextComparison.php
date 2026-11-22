<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Benchmarks\Comparison;

use Google\Protobuf\Internal\Message;
use PhpBench\Attributes as Bench;
use Thesis\Protobuf\Benchmarks\Fixture\Text;

/**
 * A string of 1 MB of valid non-ASCII UTF-8 text, which both libraries check.
 *
 * @extends Comparison<Text, Google\Text>
 */
#[Bench\Revs(100)]
final class TextComparison extends Comparison
{
    #[\Override]
    protected function message(): object
    {
        // 4-byte UTF-8 sequences.
        return new Text(str_repeat("\u{1F680}", 1 << 18));
    }

    #[\Override]
    protected function googleClass(): string
    {
        return Google\Text::class;
    }

    #[\Override]
    protected function readThesis(object $message): int
    {
        return \strlen($message->text);
    }

    #[\Override]
    protected function readGoogle(Message $message): int
    {
        return \strlen($message->getText());
    }
}
