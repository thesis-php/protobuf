<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Buffer;

use Thesis\Protobuf\Exception\BufferUnderflow;
use Thesis\Protobuf\Exception\MalformedVarint;

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
    public function readVarint(): int
    {
        $offset = $this->offset;
        $value = $this->decodeVarint($offset);
        $this->offset = $offset;

        return $value;
    }

    #[\Override]
    public function peekVarint(): int
    {
        $offset = $this->offset;

        return $this->decodeVarint($offset);
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

    /**
     * @param non-negative-int $offset where the varint starts; on return, where it ends
     * @throws BufferUnderflow
     * @throws MalformedVarint
     */
    private function decodeVarint(int &$offset): int
    {
        $value = 0;

        // Shifting a PHP int left drops the bits above the 64th, so the 10th byte contributes only its low bit.
        for ($shift = 0; $shift < 70; $shift += 7) {
            if ($offset >= $this->limit) {
                throw new BufferUnderflow('Unexpected end of input: truncated varint.');
            }

            $byte = \ord($this->bytes[$offset++]);
            $value |= ($byte & 0x7F) << $shift;

            if ($byte < 0x80) {
                return $value;
            }
        }

        throw new MalformedVarint('Malformed varint: longer than 10 bytes.');
    }

    #[\Override]
    public function count(): int
    {
        // The cursor never passes the limit: read() and pushLimit() check the bytes available.
        /** @var non-negative-int */
        return $this->limit - $this->offset;
    }
}
