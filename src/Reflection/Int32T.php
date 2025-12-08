<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection;

use BcMath\Number;

/**
 * @api
 * @template-implements Type<Number|int|numeric-string>
 */
enum Int32T implements Type
{
    case T;

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->int32($this);
    }
}
