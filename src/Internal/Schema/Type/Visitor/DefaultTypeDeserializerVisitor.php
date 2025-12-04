<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Schema\Type\Visitor;

use Thesis\Protobuf\Internal\Schema\Type\BoolT;
use Thesis\Protobuf\Internal\Schema\Type\DoubleT;
use Thesis\Protobuf\Internal\Schema\Type\EnumT;
use Thesis\Protobuf\Internal\Schema\Type\Fixed32T;
use Thesis\Protobuf\Internal\Schema\Type\Fixed64T;
use Thesis\Protobuf\Internal\Schema\Type\FloatT;
use Thesis\Protobuf\Internal\Schema\Type\Int32T;
use Thesis\Protobuf\Internal\Schema\Type\Int64T;
use Thesis\Protobuf\Internal\Schema\Type\ListT;
use Thesis\Protobuf\Internal\Schema\Type\MapT;
use Thesis\Protobuf\Internal\Schema\Type\MessageT;
use Thesis\Protobuf\Internal\Schema\Type\SFixed32T;
use Thesis\Protobuf\Internal\Schema\Type\SFixed64T;
use Thesis\Protobuf\Internal\Schema\Type\SInt32T;
use Thesis\Protobuf\Internal\Schema\Type\SInt64T;
use Thesis\Protobuf\Internal\Schema\Type\StringT;
use Thesis\Protobuf\Internal\Schema\Type\Uint32T;
use Thesis\Protobuf\Internal\Schema\Type\Uint64T;
use Thesis\Protobuf\Internal\Schema\Type\Visitor;
use Thesis\Protobuf\Internal\Serde\DeserializeValue;
use Thesis\Protobuf\Internal\Serde\SerdeBool;
use Thesis\Protobuf\Internal\Serde\SerdeDouble;
use Thesis\Protobuf\Internal\Serde\SerdeFixed32;
use Thesis\Protobuf\Internal\Serde\SerdeFixed64;
use Thesis\Protobuf\Internal\Serde\SerdeFloat;
use Thesis\Protobuf\Internal\Serde\SerdeInt32;
use Thesis\Protobuf\Internal\Serde\SerdeInt64;
use Thesis\Protobuf\Internal\Serde\SerdeSFixed32;
use Thesis\Protobuf\Internal\Serde\SerdeSFixed64;
use Thesis\Protobuf\Internal\Serde\SerdeSInt32;
use Thesis\Protobuf\Internal\Serde\SerdeSInt64;
use Thesis\Protobuf\Internal\Serde\SerdeString;
use Thesis\Protobuf\Internal\Serde\SerdeUint32;
use Thesis\Protobuf\Internal\Serde\SerdeUint64;

/**
 * @internal
 * @template-implements Visitor<DeserializeValue<*>>
 */
abstract class DefaultTypeDeserializerVisitor implements Visitor
{
    #[\Override]
    public function bool(BoolT $type): DeserializeValue
    {
        return SerdeBool::T;
    }

    #[\Override]
    public function float(FloatT $type): DeserializeValue
    {
        return SerdeFloat::T;
    }

    #[\Override]
    public function double(DoubleT $type): DeserializeValue
    {
        return SerdeDouble::T;
    }

    #[\Override]
    public function int32(Int32T $type): DeserializeValue
    {
        return SerdeInt32::T;
    }

    #[\Override]
    public function uint32(Uint32T $type): DeserializeValue
    {
        return SerdeUint32::T;
    }

    #[\Override]
    public function sint32(SInt32T $type): DeserializeValue
    {
        return SerdeSInt32::T;
    }

    #[\Override]
    public function int64(Int64T $type): DeserializeValue
    {
        return SerdeInt64::T;
    }

    #[\Override]
    public function uint64(Uint64T $type): DeserializeValue
    {
        return SerdeUint64::T;
    }

    #[\Override]
    public function sint64(SInt64T $type): DeserializeValue
    {
        return SerdeSInt64::T;
    }

    #[\Override]
    public function fixed32(Fixed32T $type): DeserializeValue
    {
        return SerdeFixed32::T;
    }

    #[\Override]
    public function sfixed32(SFixed32T $type): DeserializeValue
    {
        return SerdeSFixed32::T;
    }

    #[\Override]
    public function fixed64(Fixed64T $type): DeserializeValue
    {
        return SerdeFixed64::T;
    }

    #[\Override]
    public function sfixed64(SFixed64T $type): DeserializeValue
    {
        return SerdeSFixed64::T;
    }

    #[\Override]
    public function string(StringT $type): DeserializeValue
    {
        return SerdeString::T;
    }

    #[\Override]
    public function list(ListT $type): never
    {
        throw new \BadMethodCallException(__METHOD__);
    }

    #[\Override]
    public function map(MapT $type): never
    {
        throw new \BadMethodCallException(__METHOD__);
    }

    #[\Override]
    public function enum(EnumT $type): never
    {
        throw new \BadMethodCallException(__METHOD__);
    }

    #[\Override]
    public function message(MessageT $type): never
    {
        throw new \BadMethodCallException(__METHOD__);
    }
}
