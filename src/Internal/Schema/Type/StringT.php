<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Schema\Type;

use Thesis\Protobuf\Internal\Schema\Type;

/**
 * @internal
 * @template-implements Type<non-empty-string>
 * @template-implements Listable<non-empty-string>
 */
enum StringT implements
    Type,
    Listable
{
    /** @use Listed<non-empty-string> */
    use Listed;

    case T;

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->string($this);
    }
}
