<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection;

/**
 * @api
 * @template-implements Type<string>
 */
enum StringT implements Type
{
    case T;

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->string($this);
    }
}
