<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

/**
 * @api
 */
final class ByteBuffer implements Buffer
{
    /** @var non-negative-int */
    private int $length;

    public function __construct(
        private string $buffer = '',
    ) {
        $this->length = \strlen($this->buffer);
    }

    public function write(string $value): void
    {
        $this->buffer .= $value;
        $this->length += \strlen($value);
    }

    public function peek(int $n): string
    {
        $value = substr($this->buffer, 0, $n);
        if ($value === '') {
            throw new BufferUnderflow('Buffer is empty.');
        }

        return $value;
    }

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

    public function flush(): string
    {
        $buffer = $this->buffer;
        $this->buffer = '';
        $this->length = 0;

        return $buffer;
    }

    public function __toString(): string
    {
        return $this->buffer;
    }

    /**
     * @return non-negative-int
     */
    public function count(): int
    {
        return $this->length;
    }
}
