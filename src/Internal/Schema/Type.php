<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Schema;

use Thesis\Protobuf\Internal\Schema\Type\Visitor;

/**
 * @internal
 * @template-covariant T
 * @template Repeatable of 'repeatable' | 'not-repeatable' = 'repeatable'
 * @template Indexed of 'indexed' | 'not-indexed' = 'indexed'
 * @template MapValue of 'map-value' | 'not-map-value' = 'map-value'
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
