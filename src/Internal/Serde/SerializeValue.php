<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Protobuf\Internal\Buffer\WriteBuffer;

/**
 * @internal
 * @template T
 */
interface SerializeValue
{
    /**
     * @param T $value
     */
    public function serialize(WriteBuffer $buffer, mixed $value): void;
}
