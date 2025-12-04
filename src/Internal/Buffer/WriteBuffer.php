<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Buffer;

/**
 * @internal
 */
interface WriteBuffer extends \Countable
{
    /**
     * @param non-empty-string $value
     */
    public function write(string $value): void;
}
