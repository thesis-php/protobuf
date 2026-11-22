<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Benchmarks;

use PhpBench\Attributes as Bench;
use Thesis\Protobuf\Benchmarks\Fixture\Blob;
use Thesis\Protobuf\Benchmarks\Fixture\Text;

/**
 * 1 MB of bytes and 1 MB of valid non-ASCII UTF-8 text: the same on the wire, but a string is checked for valid UTF-8
 * unless the check is turned off.
 *
 * @extends CodecBench<array{kind: 'bytes'|'string', validateUtf8?: bool}>
 */
#[Bench\Revs(1000)]
#[Bench\ParamProviders('provideKinds')]
final class BlobBench extends CodecBench
{
    /**
     * @return iterable<string, array{kind: 'bytes'|'string', validateUtf8?: bool}>
     */
    public function provideKinds(): iterable
    {
        yield 'bytes 1MB' => ['kind' => 'bytes'];
        yield 'string 1MB' => ['kind' => 'string'];
        yield 'string 1MB unchecked' => ['kind' => 'string', 'validateUtf8' => false];
    }

    #[\Override]
    protected function validateUtf8(array $params): bool
    {
        return $params['validateUtf8'] ?? true;
    }

    #[\Override]
    protected function message(array $params): object
    {
        return match ($params['kind']) {
            'bytes' => new Blob(str_repeat("\xAB", 1 << 20)),
            // 4-byte UTF-8 sequences.
            default => new Text(str_repeat("\u{1F680}", 1 << 18)),
        };
    }
}
