<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Type;

use Thesis\Protobuf\Type;

/**
 * @internal
 * @template-implements Type<int>
 * @template-implements Listable<int>
 */
enum SFixed32T implements
    Type,
    Listable
{
    /** @use Listed<int> */
    use Listed;

    case T;

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->sfixed32($this);
    }
}
