<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Buffer;

/**
 * An append-only output buffer.
 *
 * @internal
 */
final class ByteBuffer implements
    WriteBuffer,
    \Stringable
{
    /** @var non-negative-int */
    private int $length = 0;

    private string $buffer = '';

    #[\Override]
    public function write(string $value): void
    {
        $this->buffer .= $value;
        $this->length += \strlen($value);
    }

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
