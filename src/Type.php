<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

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
     * @param Type\Visitor<TResult> $visitor
     * @return TResult
     */
    public function accept(Type\Visitor $visitor): mixed;
}
