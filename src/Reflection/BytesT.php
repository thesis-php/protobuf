<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection;

/**
 * @api
 * @template-implements Type<string>
 */
enum BytesT implements Type
{
    case T;

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->bytes($this);
    }
}
