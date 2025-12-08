<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection;

use BcMath\Number;

/**
 * @api
 * @template-implements Type<Number|non-negative-int|numeric-string>
 */
enum Uint64T implements Type
{
    case T;

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->uint64($this);
    }
}
