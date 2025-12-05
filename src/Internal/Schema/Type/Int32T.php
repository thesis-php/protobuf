<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Schema\Type;

use BcMath\Number;
use Thesis\Protobuf\Internal\Schema\Type;

/**
 * @internal
 * @template-implements Type<Number|int|numeric-string>
 * @template-implements Listable<Number|int|numeric-string>
 */
enum Int32T implements
    Type,
    Listable
{
    /** @use Listed<Number|int|numeric-string> */
    use Listed;

    case T;

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->int32($this);
    }
}
