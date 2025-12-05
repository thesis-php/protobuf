<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Schema\Type;

use Thesis\Endian;
use Thesis\Protobuf\Internal\Schema\Type;

/**
 * @internal
 * @phpstan-import-type Int32 from Endian\Order
 * @template-implements Type<Int32>
 * @template-implements Listable<Int32>
 */
enum SFixed32T implements
    Type,
    Listable
{
    /** @use Listed<Int32> */
    use Listed;

    case T;

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->sfixed32($this);
    }
}
