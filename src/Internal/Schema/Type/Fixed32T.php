<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Schema\Type;

use Thesis\Endian;
use Thesis\Protobuf\Internal\Schema\Type;

/**
 * @internal
 * @phpstan-import-type Uint32 from Endian\Order
 * @template-implements Type<Uint32>
 * @template-implements Listable<Uint32>
 */
enum Fixed32T implements
    Type,
    Listable
{
    /** @use Listed<Uint32> */
    use Listed;

    case T;

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->fixed32($this);
    }
}
