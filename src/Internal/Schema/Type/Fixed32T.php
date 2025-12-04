<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Schema\Type;

use Thesis\Endian;
use Thesis\Protobuf;
use Thesis\Protobuf\Internal\Schema\Type;

/**
 * @internal
 * @phpstan-import-type Uint32 from Endian\Order
 * @template-implements Type<Uint32>
 */
enum Fixed32T implements Type
{
    case T;

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->fixed32($this);
    }

    /**
     * @param list<Uint32> $values
     * @return Protobuf\Value<list<Uint32>>
     */
    public function list(array $values): Protobuf\Value
    {
        return Protobuf\listOf($this, $values);
    }
}
