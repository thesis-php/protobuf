<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Type;

use Thesis\Protobuf\Type;

/**
 * Arbitrary bytes. On the wire the same as a string, but never checked for valid UTF-8, and, as protobuf requires,
 * not a map key.
 *
 * @template-implements Type<string, 'repeatable', 'not-indexed'>
 * @template-implements Listable<string>
 */
enum BytesT implements
    Type,
    Listable
{
    /** @use Listed<string> */
    use Listed;

    case T;

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->bytes($this);
    }
}
