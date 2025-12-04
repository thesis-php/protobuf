<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Schema\Type;

use BcMath\Number;
use Thesis\Protobuf;
use Thesis\Protobuf\Internal\Schema\Type;

/**
 * @internal
 * @template-implements Type<Number|non-negative-int|numeric-string>
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
     * @return Protobuf\Value<list<Number|non-negative-int|numeric-string>>
     */
    public function list(array $values): Protobuf\Value
    {
        return Protobuf\listOf($this, $values);
    }
}
