<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Schema\Type;

use BcMath\Number;
use Thesis\Protobuf\Internal\Schema\Type;

/**
 * @internal
 * @template-implements Type<Number|non-negative-int|numeric-string>
 * @template-implements Listable<Number|non-negative-int|numeric-string>
 */
enum Uint32T implements
    Type,
    Listable
{
    /** @use Listed<Number|non-negative-int|numeric-string> */
    use Listed;

    case T;

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->uint32($this);
    }
}
