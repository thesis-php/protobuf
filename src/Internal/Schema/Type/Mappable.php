<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Schema\Type;

use Thesis\Protobuf;

/**
 * @internal
 * @template K of array-key
 * @template V
 */
interface Mappable
{
    /**
     * @param array<K, V> $values
     * @return Protobuf\Value<array<K, V>>
     */
    public function map(array $values): Protobuf\Value;
}
