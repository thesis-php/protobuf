<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Schema\Type;

use Thesis\Protobuf\Internal\Schema\Type;

/**
 * @internal
 * @template-implements Type<float, 'repeatable', 'not-indexed'>
 * @template-implements Listable<float>
 */
enum FloatT implements
    Type,
    Listable
{
    /** @use Listed<float> */
    use Listed;

    case T;

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->float($this);
    }
}
