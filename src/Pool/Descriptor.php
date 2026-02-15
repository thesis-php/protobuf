<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Pool;

/**
 * @api
 */
final class Descriptor
{
    /** @phpstan-ignore property.uninitialized */
    public private(set) string $bytes { get => $this->bytes ??= ($this->decode)($this->buffer); }

    /**
     * @param non-empty-string $buffer
     */
    public static function base64(string $buffer): self
    {
        return new self($buffer, \base64_decode(...)); // @phpstan-ignore argument.type
    }

    /**
     * @param non-empty-string $buffer
     */
    public static function raw(string $buffer): self
    {
        return new self($buffer, static fn(string $buffer) => $buffer);
    }

    /**
     * @param non-empty-string $buffer
     * @param \Closure(non-empty-string): non-empty-string $decode
     */
    private function __construct(
        private readonly string $buffer,
        private readonly \Closure $decode,
    ) {}
}
