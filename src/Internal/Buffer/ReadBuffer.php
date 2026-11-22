<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Buffer;

use Thesis\Protobuf\Exception\BufferUnderflow;
use Thesis\Protobuf\Exception\MalformedVarint;
use Thesis\Protobuf\Exception\RecursionLimitExceeded;

/**
 * @internal
 */
interface ReadBuffer extends \Countable
{
    /**
     * Whether a string field read from the buffer must be valid UTF-8.
     */
    public bool $validateUtf8 { get; }

    /**
     * Reads a varint as the 64 bits it encodes: values of 2^63 and above come back as negative ints.
     * Bits above the 64th, which only the 10th byte can carry, are dropped.
     *
     * @throws BufferUnderflow if the input or the current limit ends inside the varint
     * @throws MalformedVarint if the varint does not end within 10 bytes
     */
    public function readVarint(): int;

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

    /**
     * Counts a level of nesting, a nested message or group, against the recursion limit, until leaveNested().
     *
     * @throws RecursionLimitExceeded if the levels entered and not left exceed the limit
     */
    public function enterNested(): void;

    public function leaveNested(): void;

    /**
     * The offset of the cursor in the input, to take the bytes read from there on with bytesSince().
     *
     * @return non-negative-int
     */
    public function position(): int;

    /**
     * Returns the bytes read since the cursor was at `position`, e.g. the body of a record read field by field,
     * without moving the cursor.
     *
     * @param non-negative-int $position a position() taken earlier
     */
    public function bytesSince(int $position): string;
}
