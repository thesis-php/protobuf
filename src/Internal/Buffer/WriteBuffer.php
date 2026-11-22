<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Buffer;

/**
 * @internal
 */
interface WriteBuffer extends \Countable
{
    /**
     * Whether a string field written to the buffer must be valid UTF-8. A buffer for a nested message or a map entry
     * takes it from the buffer it is written to.
     */
    public bool $validateUtf8 { get; }

    /**
     * @param non-empty-string $value
     */
    public function write(string $value): void;
}
