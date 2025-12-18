<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Type;

use BcMath\Number;
use Thesis\Protobuf\Type;

/**
 * @template-implements Type<Number>
 * @template-implements Listable<Number>
 */
enum SInt64T implements
    Type,
    Listable
{
    /** @use Listed<Number> */
    use Listed;

    case T;

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->sint64($this);
    }
}
