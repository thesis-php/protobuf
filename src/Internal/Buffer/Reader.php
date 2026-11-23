<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Buffer;

use Thesis\Protobuf\Exception\BufferUnderflow;
use Thesis\Protobuf\Exception\MalformedVarint;
use Thesis\Protobuf\Exception\RecursionLimitExceeded;
use Thesis\Protobuf\Serializer;

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

    /** The levels of nesting left to enter: below zero, the limit is exceeded. */
    private int $depth;

    /**
     * @param non-negative-int $recursionLimit how many messages and groups may be nested in one another
     * @param bool $validateUtf8 whether a string field must be valid UTF-8
     */
    public function __construct(
        private readonly string $bytes,
        private readonly int $recursionLimit = Serializer::DEFAULT_RECURSION_LIMIT,
        public readonly bool $validateUtf8 = true,
    ) {
        $this->limit = \strlen($bytes);
        $this->depth = $recursionLimit;
    }

    #[\Override]
    public function readVarint(): int
    {
        $offset = $this->offset;

        // Most varints are one byte: small numbers, bools, lengths of short strings.
        if ($offset < $this->limit && ($byte = \ord($this->bytes[$offset])) < 0x80) {
            $this->offset = $offset + 1;

            return $byte;
        }

        $value = 0;

        // Shifting a PHP int left drops the bits above the 64th, so the 10th byte contributes only its low bit.
        for ($shift = 0; $shift < 70; $shift += 7) {
            if ($offset >= $this->limit) {
                throw new BufferUnderflow('Unexpected end of input: truncated varint.');
            }

            $byte = \ord($this->bytes[$offset++]);
            $value |= ($byte & 0x7F) << $shift;

            if ($byte < 0x80) {
                $this->offset = $offset;

                return $value;
            }
        }

        throw new MalformedVarint('Malformed varint: longer than 10 bytes.');
    }

    #[\Override]
    public function readTag(): int
    {
        $offset = $this->offset;

        // The tag of a field numbered up to 15 is one byte.
        if ($offset < $this->limit && ($byte = \ord($this->bytes[$offset])) < 0x80) {
            $this->offset = $offset + 1;

            return $byte;
        }

        $end = $offset + 5;
        $value = 0;
        $shift = 0;

        while ($offset < $end) {
            if ($offset >= $this->limit) {
                throw new BufferUnderflow('Unexpected end of input: truncated varint.');
            }

            $byte = \ord($this->bytes[$offset++]);
            $value |= ($byte & 0x7F) << $shift;

            if ($byte < 0x80) {
                $this->offset = $offset;

                return $value;
            }

            $shift += 7;
        }

        throw new MalformedVarint('Malformed tag: longer than 5 bytes.');
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
    public function enterNested(): void
    {
        if (--$this->depth < 0) {
            throw new RecursionLimitExceeded($this->recursionLimit);
        }
    }

    #[\Override]
    public function leaveNested(): void
    {
        ++$this->depth;
    }

    #[\Override]
    public function position(): int
    {
        return $this->offset;
    }

    #[\Override]
    public function bytesSince(int $position): string
    {
        // A position taken later lies past the cursor.
        \assert($position <= $this->offset, 'The position must be taken before the bytes are read.');

        return substr($this->bytes, $position, $this->offset - $position);
    }

    #[\Override]
    public function count(): int
    {
        // The cursor never passes the limit: read() and pushLimit() check the bytes available.
        /** @var non-negative-int */
        return $this->limit - $this->offset;
    }
}
