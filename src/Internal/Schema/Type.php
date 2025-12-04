<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Schema;

use Thesis\Protobuf\Internal\Schema\Type\Visitor;

/**
 * @internal
 * @template-covariant T
 */
interface Type
{
    /**
     * @template TResult
     * @param Visitor<TResult> $visitor
     * @return TResult
     */
    public function accept(Visitor $visitor): mixed;
}
