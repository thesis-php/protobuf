<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Schema\Type;

use Thesis\Protobuf;

/**
 * @internal
 * @template T
 */
interface Listable
{
    /**
     * @param list<T> $values
     * @return Protobuf\Value<list<T>>
     */
    public function list(array $values): Protobuf\Value;
}
