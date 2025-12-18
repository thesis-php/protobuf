<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Type;

use Thesis\Protobuf;

/**
 * @internal
 * @template K
 * @template V
 */
interface Mappable
{
    /**
     * @param Protobuf\Map<K, V> $map
     * @return Protobuf\Value<Protobuf\Map<K, V>>
     */
    public function map(Protobuf\Map $map): Protobuf\Value;
}
