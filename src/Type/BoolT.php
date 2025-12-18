<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Type;

use Thesis\Protobuf\Type;

/**
 * @template-implements Type<bool>
 * @template-implements Listable<bool>
 */
enum BoolT implements
    Type,
    Listable
{
    /** @use Listed<bool> */
    use Listed;

    case T;

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->bool($this);
    }
}
