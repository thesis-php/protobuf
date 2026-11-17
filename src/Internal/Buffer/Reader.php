<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Buffer;

use Thesis\Protobuf\Exception\BufferUnderflow;

/**
 * Reads the input in place: a cursor moves over the original string and a nested record is read by narrowing
 * the limit, so no part of the input is copied except the values returned.
 *
 * @internal
 */
final class Reader implements ReadBuffer
{
    /** @var non-negative-int */
    private int $offset = 0;

    /** @var non-negative-int */
    private int $limit;

    public function __construct(
        private readonly string $bytes,
    ) {
        $this->limit = \strlen($bytes);
    }

    #[\Override]
    public function peek(int $n): string
    {
        if ($this->offset >= $this->limit) {
            throw new BufferUnderflow('Buffer is empty.');
        }

        /** @var non-empty-string */
        return substr($this->bytes, $this->offset, min($n, $this->limit - $this->offset));
    }

    #[\Override]
    public function read(int $n): string
    {
        $available = $this->limit - $this->offset;
        if ($available < $n) {
            throw new BufferUnderflow("Expected '{$n}' bytes, but the buffer only has '{$available}' bytes.");
        }

        /** @var non-empty-string $value */
        $value = substr($this->bytes, $this->offset, $n);
        $this->offset += $n;

        return $value;
    }

    #[\Override]
    public function pushLimit(int $length): int
    {
        $available = $this->limit - $this->offset;
        if ($length < 0 || $length > $available) {
            throw new BufferUnderflow("Expected '{$length}' bytes, but the buffer only has '{$available}' bytes.");
        }

        $limit = $this->limit;
        $this->limit = $this->offset + $length;

        return $limit;
    }

    #[\Override]
    public function popLimit(int $limit): void
    {
        // A limit pushed later, or restored out of order, would lie behind the cursor or past the input.
        \assert($limit >= $this->offset && $limit <= \strlen($this->bytes), 'Limits must be restored in reverse order.');

        $this->limit = $limit;
    }

    #[\Override]
    public function count(): int
    {
        // The cursor never passes the limit: read() and pushLimit() check the bytes available.
        /** @var non-negative-int */
        return $this->limit - $this->offset;
    }
}
