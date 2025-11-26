<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

/**
 * @api
 */
interface Buffer extends
    \Countable,
    \Stringable
{
    /**
     * @param non-empty-string $value
     */
    public function write(string $value): void;

    /**
     * Peek should not move the cursor or remove data from the buffer.
     * It should only return a `non-empty-string` of up to `n` bytes in size or throw an exception only if the buffer is empty.
     *
     * @param positive-int $n
     * @return non-empty-string
     * @throws BufferUnderflow
     */
    public function peek(int $n): string;

    /**
     * Read must remove `n` bytes of data from the buffer and return a `non-empty-string`, or throw an exception
     * if the buffer is empty or its length is less than the required `n` bytes.
     *
     * @param positive-int $n
     * @return non-empty-string
     * @throws BufferUnderflow
     */
    public function read(int $n): string;

    /**
     * Flush should discard all data in the buffer and return it.
     */
    public function flush(): string;
}
