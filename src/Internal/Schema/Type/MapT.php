<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Schema\Type;

use Thesis\Protobuf;
use Thesis\Protobuf\Internal\Schema\Type;

/**
 * @internal
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
