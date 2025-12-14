<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Schema\Type;

use BcMath\Number;
use Thesis\Protobuf\Internal\Schema\Type;

/**
 * @internal
 * @template-implements Type<Number>
 * @template-implements Listable<Number>
 */
enum Uint64T implements
    Type,
    Listable
{
    /** @use Listed<Number> */
    use Listed;

    case T;

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->uint64($this);
    }
}
