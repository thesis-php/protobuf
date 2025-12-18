<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection\Internal\Visitor;

use Thesis\Protobuf;
use Thesis\Protobuf\Reflection\BoolT;
use Thesis\Protobuf\Reflection\BytesT;
use Thesis\Protobuf\Reflection\DoubleT;
use Thesis\Protobuf\Reflection\EnumT;
use Thesis\Protobuf\Reflection\Fixed32T;
use Thesis\Protobuf\Reflection\Fixed64T;
use Thesis\Protobuf\Reflection\FloatT;
use Thesis\Protobuf\Reflection\Int32T;
use Thesis\Protobuf\Reflection\Int64T;
use Thesis\Protobuf\Reflection\ListT;
use Thesis\Protobuf\Reflection\MapT;
use Thesis\Protobuf\Reflection\ObjectT;
use Thesis\Protobuf\Reflection\Reflector;
use Thesis\Protobuf\Reflection\SFixed32T;
use Thesis\Protobuf\Reflection\SFixed64T;
use Thesis\Protobuf\Reflection\SInt32T;
use Thesis\Protobuf\Reflection\SInt64T;
use Thesis\Protobuf\Reflection\StringT;
use Thesis\Protobuf\Reflection\Uint32T;
use Thesis\Protobuf\Reflection\Uint64T;
use Thesis\Protobuf\Reflection\Visitor;
use Thesis\Protobuf\Type;

/**
 * @internal
 * @template-implements Visitor<Type<mixed, *, *, *>>
 */
final readonly class ToProtobufTypeTypeVisitor implements Visitor
{
    public function __construct(
        private Reflector $reflector,
    ) {}

    #[\Override]
    public function bool(BoolT $type): Type
    {
        return Protobuf\boolT;
    }

    #[\Override]
    public function float(FloatT $type): Type
    {
        return Protobuf\floatT;
    }

    #[\Override]
    public function double(DoubleT $type): Type
    {
        return Protobuf\doubleT;
    }

    #[\Override]
    public function int32(Int32T $type): Type
    {
        return Protobuf\int32T;
    }

    #[\Override]
    public function uint32(Uint32T $type): Type
    {
        return Protobuf\uint32T;
    }

    #[\Override]
    public function sint32(SInt32T $type): Type
    {
        return Protobuf\sint32T;
    }

    #[\Override]
    public function int64(Int64T $type): Type
    {
        return Protobuf\int64T;
    }

    #[\Override]
    public function uint64(Uint64T $type): Type
    {
        return Protobuf\uint64T;
    }

    #[\Override]
    public function sint64(SInt64T $type): Type
    {
        return Protobuf\sint64T;
    }

    #[\Override]
    public function fixed32(Fixed32T $type): Type
    {
        return Protobuf\fixed32T;
    }

    #[\Override]
    public function sfixed32(SFixed32T $type): Type
    {
        return Protobuf\sfixed32T;
    }

    #[\Override]
    public function fixed64(Fixed64T $type): Type
    {
        return Protobuf\fixed64T;
    }

    #[\Override]
    public function sfixed64(SFixed64T $type): Type
    {
        return Protobuf\sfixed64T;
    }

    #[\Override]
    public function string(StringT $type): Type
    {
        return Protobuf\stringT;
    }

    #[\Override]
    public function bytes(BytesT $type): Type
    {
        return Protobuf\bytesT;
    }

    #[\Override]
    public function list(ListT $type): Type
    {
        /** @var Type<mixed, 'repeatable'> $element */
        $element = $type->element->accept($this);

        return Protobuf\listT($element);
    }

    #[\Override]
    public function map(MapT $type): Type
    {
        /** @var Type<mixed, *, 'indexed', 'map-value'> $key */
        $key = $type->keyT->accept($this);

        /** @var Type<mixed, *, *, 'map-value'> $value */
        $value = $type->valueT->accept($this);

        return Protobuf\mapT(
            $key,
            $value,
        );
    }

    #[\Override]
    public function enum(EnumT $type): Type
    {
        return Protobuf\enumT($type->enum);
    }

    #[\Override]
    public function object(ObjectT $type): Type
    {
        return $this->reflector->type($type->class);
    }
}
