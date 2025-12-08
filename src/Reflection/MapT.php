<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection;

/**
 * @api
 * @template K
 * @template V
 * @template-implements Type<iterable<K, V>, 'not-repeatable', 'not-indexed', 'not-map-value'>
 */
final readonly class MapT implements Type
{
    /**
     * @param Type<K, *, 'indexed', *> $keyT
     * @param Type<V, *, *, 'map-value'> $valueT
     */
    public function __construct(
        public Type $keyT,
        public Type $valueT,
    ) {}

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->map($this);
    }
}
