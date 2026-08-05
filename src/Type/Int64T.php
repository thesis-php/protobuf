<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Type;

use Thesis\Protobuf\Type;

/**
 * @template-implements Type<int>
 * @template-implements Listable<int>
 */
enum Int64T implements
    Type,
    Listable
{
    /** @use Listed<int> */
    use Listed;

    case T;

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->int64($this);
    }
}
