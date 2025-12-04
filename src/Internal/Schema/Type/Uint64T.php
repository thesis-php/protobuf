<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Schema\Type;

use BcMath\Number;
use Thesis\Protobuf;
use Thesis\Protobuf\Internal\Schema\Type;

/**
 * @internal
 * @template-implements Type<Number>
 */
enum Uint64T implements Type
{
    case T;

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->uint64($this);
    }

    /**
     * @param list<Number|non-negative-int|numeric-string> $values
     * @return Protobuf\Value<list<Number>>
     */
    public function list(array $values): Protobuf\Value
    {
        return Protobuf\listOf(
            $this,
            array_map(Protobuf\toNumber(...), $values),
        );
    }
}
