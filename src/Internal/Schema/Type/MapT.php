<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Schema\Type;

use Thesis\Protobuf\Internal\Schema\Type;

/**
 * @internal
 * @template K of array-key
 * @template V
 * @template-implements Type<array<K, V>>
 */
final readonly class MapT implements Type
{
    /**
     * @param Type<K> $keyT
     * @param Type<V> $valueT
     * @throws \UnexpectedValueException
     */
    public function __construct(
        public Type $keyT,
        public Type $valueT,
    ) {
        $this->keyT->accept(new Type\Visitor\ValidateMapKeyTypeVisitor());
        $this->valueT->accept(new Type\Visitor\ValidateMapValueTypeVisitor());
    }

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->map($this);
    }
}
