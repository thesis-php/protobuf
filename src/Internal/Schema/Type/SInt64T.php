<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Schema\Type;

use BcMath\Number;
use Thesis\Protobuf;
use Thesis\Protobuf\Internal\Schema\Type;

/**
 * @internal
 * @template-implements Type<Number|int|numeric-string>
 */
enum SInt64T implements Type
{
    case T;

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->sint64($this);
    }

    /**
     * @param list<Number|int|numeric-string> $values
     * @return Protobuf\Value<list<Number|int|numeric-string>>
     */
    public function list(array $values): Protobuf\Value
    {
        return Protobuf\listOf($this, $values);
    }
}
