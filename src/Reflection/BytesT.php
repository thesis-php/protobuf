<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection;

/**
 * Arbitrary bytes, never checked for valid UTF-8. As protobuf requires, not a map key.
 *
 * @api
 * @template-implements Type<string, 'repeatable', 'not-indexed'>
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
