<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection;

/**
 * @api
 * @template-implements Type<double, 'repeatable', 'not-indexed'>
 */
enum DoubleT implements Type
{
    case T;

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->double($this);
    }
}
