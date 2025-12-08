<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection;

use BcMath\Number;

/**
 * @api
 * @template-implements Type<Number|int|numeric-string>
 */
enum SFixed64T implements Type
{
    case T;

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->sfixed64($this);
    }
}
