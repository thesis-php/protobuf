<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection;

/**
 * A map field. The property holds the map as a {@see \Thesis\Protobuf\Map} or as an array, and is encoded from any
 * iterable of its entries. It is decoded as a Map if its type accepts one (Map, an interface of it, iterable, object,
 * mixed, no type, or a union with one of them), and as an array otherwise, e.g. for array or ?array. Only int and
 * string keys can be array keys, so a property that holds the map as an array is rejected for bool, uint64 and fixed64
 * keys; PHP turns a numeric string key into an int, as for any array.
 *
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
