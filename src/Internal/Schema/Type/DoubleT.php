<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Schema\Type;

use Thesis\Protobuf;
use Thesis\Protobuf\Internal\Schema\Type;

/**
 * @internal
 * @template-implements Type<double>
 */
enum DoubleT implements Type
{
    case T;

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->double($this);
    }

    /**
     * @param list<double> $values
     * @return Protobuf\Value<list<double>>
     */
    public function list(array $values): Protobuf\Value
    {
        return Protobuf\listOf($this, $values);
    }
}
