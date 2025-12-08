<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection;

/**
 * @api
 * @template-implements Type<float, 'repeatable', 'not-indexed'>
 */
enum FloatT implements Type
{
    case T;

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->float($this);
    }
}
