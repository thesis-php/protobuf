<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection;

use Thesis\Endian;

/**
 * @api
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
}
