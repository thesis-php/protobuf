<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection;

use Thesis\Endian;

/**
 * @api
 * @phpstan-import-type Int32 from Endian\Order
 * @template-implements Type<Int32>
 */
enum SFixed32T implements Type
{
    case T;

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->sfixed32($this);
    }
}
