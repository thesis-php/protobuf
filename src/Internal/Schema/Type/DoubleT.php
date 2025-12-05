<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Schema\Type;

use Thesis\Protobuf\Internal\Schema\Type;

/**
 * @internal
 * @template-implements Type<double, 'repeatable', 'not-indexed'>
 * @template-implements Listable<double>
 */
enum DoubleT implements
    Type,
    Listable
{
    /** @use Listed<double> */
    use Listed;

    case T;

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->double($this);
    }
}
