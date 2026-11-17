<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Buffer;

use Thesis\Protobuf\Exception\BufferUnderflow;

/**
 * @internal
 */
interface ReadBuffer extends \Countable
{
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
     * Limits the buffer to the next `length` bytes, e.g. to the body of a nested message: count() and reads
     * see only them. Returns the previous limit to restore with popLimit() once they are read.
     *
     * @throws BufferUnderflow if fewer than `length` bytes are left
     */
    public function pushLimit(int $length): int;

    public function popLimit(int $limit): void;
}
