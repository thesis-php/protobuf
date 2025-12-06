<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Schema\Type;

use Thesis\Protobuf;

/**
 * @internal
 * @template K
 * @template V
 */
interface Mappable
{
    /**
     * @param iterable<K, V> $values
     * @return Protobuf\Value<iterable<K, V>>
     */
    public function map(iterable $values): Protobuf\Value;
}
