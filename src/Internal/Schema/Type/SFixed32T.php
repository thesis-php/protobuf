<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Schema\Type;

use Thesis\Endian;
use Thesis\Protobuf;
use Thesis\Protobuf\Internal\Schema\Type;

/**
 * @internal
 * @phpstan-import-type Int32 from Endian\Order
 * @template-implements Type<Int32>
 */
enum SFixed32T implements Type
{
    case T;

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->sfixed32($this);
    }

    /**
     * @param list<Int32> $values
     * @return Protobuf\Value<list<Int32>>
     */
    public function list(array $values): Protobuf\Value
    {
        return Protobuf\listOf($this, $values);
    }
}
