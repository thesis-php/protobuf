<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Buffer;

use Thesis\Protobuf\BufferUnderflow;

/**
 * @internal
 */
final class ByteBuffer implements
    WriteBuffer,
    ReadBuffer,
    \Stringable
{
    /** @var non-negative-int */
    private int $length;

    public function __construct(
        private string $buffer = '',
    ) {
        $this->length = \strlen($this->buffer);
    }

    #[\Override]
    public function write(string $value): void
    {
        $this->buffer .= $value;
        $this->length += \strlen($value);
    }

    #[\Override]
    public function peek(int $n): string
    {
        $value = substr($this->buffer, 0, $n);
        if ($value === '') {
            throw new BufferUnderflow('Buffer is empty.');
        }

        return $value;
    }

    #[\Override]
    public function read(int $n): string
    {
        if ($this->length < $n) {
            throw new BufferUnderflow("Expected '{$n}' bytes, but the buffer only has '{$this->length}' bytes.");
        }

        /** @var non-empty-string $buffer */
        $buffer = substr($this->buffer, 0, $n);
        $this->buffer = substr($this->buffer, $n);

        /** @phpstan-ignore assign.propertyType */
        $this->length -= $n;

        return $buffer;
    }

    #[\Override]
    public function flush(): string
    {
        $buffer = $this->buffer;
        $this->buffer = '';
        $this->length = 0;

        return $buffer;
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->buffer;
    }

    #[\Override]
    public function count(): int
    {
        return $this->length;
    }
}
