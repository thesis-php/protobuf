<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection;

use Thesis\Protobuf\Exception\InvalidSchema;

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
     * @throws InvalidSchema if the key type is not an integer, bool or string, or the value type is a list or a map
     */
    public function __construct(
        public Type $keyT,
        public Type $valueT,
    ) {
        if (!$keyT instanceof Int32T
            && !$keyT instanceof Int64T
            && !$keyT instanceof Uint32T
            && !$keyT instanceof Uint64T
            && !$keyT instanceof SInt32T
            && !$keyT instanceof SInt64T
            && !$keyT instanceof Fixed32T
            && !$keyT instanceof Fixed64T
            && !$keyT instanceof SFixed32T
            && !$keyT instanceof SFixed64T
            && !$keyT instanceof BoolT
            && !$keyT instanceof StringT
        ) {
            throw new InvalidSchema(\sprintf(
                'Map keys of type "%s" are not allowed: only integer, bool and string keys are.',
                $keyT::class,
            ));
        }

        if ($valueT instanceof ListT || $valueT instanceof self) {
            throw new InvalidSchema('Map values cannot be lists or maps: protobuf has no such fields.');
        }
    }

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->map($this);
    }
}
