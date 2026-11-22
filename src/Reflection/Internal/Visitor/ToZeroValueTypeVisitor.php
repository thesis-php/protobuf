<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection\Internal\Visitor;

use Thesis\Protobuf;
use Thesis\Protobuf\Internal\Serde\ZeroValue;
use Thesis\Protobuf\Map;
use Thesis\Protobuf\Reflection\ListT;
use Thesis\Protobuf\Reflection\MapT;
use Thesis\Protobuf\Reflection\ObjectT;
use Thesis\Protobuf\Reflection\Type;
use Thesis\Protobuf\Reflection\Visitor;

/**
 * Builds once per property what a missing field gives the property when missing fields are zero values, as proto3
 * reads a field without presence: the zero value of a scalar or an enum as the deserializer has it for a missing map
 * key or value (see Serde\ZeroValue), an empty list, an empty map. A map is an object the property may change, so
 * each object gets a new one.
 *
 * A message field has no zero value, nor has an enum without cases: null stands for none, and the property stays
 * required.
 *
 * @internal
 * @template-extends DefaultTypeVisitor<?\Closure(): mixed>
 */
final class ToZeroValueTypeVisitor extends DefaultTypeVisitor
{
    private readonly ZeroValue $zero;

    /**
     * @param Visitor<Protobuf\Type<mixed, *, *, *>> $protobufType
     */
    public function __construct(
        private readonly Visitor $protobufType,
    ) {
        $this->zero = new ZeroValue();
    }

    #[\Override]
    public function list(ListT $type): \Closure
    {
        return static fn(): array => [];
    }

    #[\Override]
    public function map(MapT $type): \Closure
    {
        return static fn(): Map => new Map();
    }

    /**
     * The zero value of a map field for a property that holds the map as an array.
     *
     * @param MapT<mixed, mixed> $type
     * @return \Closure(): array{}
     */
    public function mapToArray(MapT $type): \Closure
    {
        return static fn(): array => [];
    }

    #[\Override]
    public function object(ObjectT $type): null
    {
        return null;
    }

    /**
     * A scalar or an enum.
     */
    #[\Override]
    protected function default(Type $type): ?\Closure
    {
        $zero = $type->accept($this->protobufType)->accept($this->zero);

        return $zero === null ? null : static fn(): mixed => $zero;
    }
}
