<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Type;

use Thesis\Protobuf;
use Thesis\Protobuf\Exception\InvalidSchema;
use Thesis\Protobuf\Type;

/**
 * @template K
 * @template V
 * @template-implements Type<Protobuf\Map<K, V>, 'not-repeatable', 'not-indexed', 'not-map-value'>
 * @template-implements Mappable<K, V>
 */
final readonly class MapT implements Type, Mappable
{
    /**
     * @param Type<K, *, 'indexed'> $keyT
     * @param Type<V, *, *, 'map-value'> $valueT
     * @throws InvalidSchema if the key type is not an integer, bool or string, or the value type is a list, a map or a group
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

        if ($valueT instanceof GroupT) {
            throw new InvalidSchema('Map values cannot be groups: a map entry holds its value as a message after its length.');
        }
    }

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->map($this);
    }

    #[\Override]
    public function map(Protobuf\Map $map): Protobuf\Value
    {
        return Protobuf\mapOf(
            $this->keyT,
            $this->valueT,
            $map,
        );
    }
}
