<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Benchmarks;

use PhpBench\Attributes as Bench;
use Thesis\Protobuf\Benchmarks\Fixture\Blob;
use Thesis\Protobuf\Benchmarks\Fixture\Text;

/**
 * 1 MB of bytes and 1 MB of valid non-ASCII UTF-8 text: identical today, they part ways once strings are validated.
 *
 * @extends CodecBench<array{kind: 'bytes'|'string'}>
 */
#[Bench\Revs(1000)]
#[Bench\ParamProviders('provideKinds')]
final class BlobBench extends CodecBench
{
    /**
     * @return iterable<string, array{kind: 'bytes'|'string'}>
     */
    public function provideKinds(): iterable
    {
        yield 'bytes 1MB' => ['kind' => 'bytes'];
        yield 'string 1MB' => ['kind' => 'string'];
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
