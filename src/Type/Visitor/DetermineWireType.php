<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Type\Visitor;

use Thesis\Protobuf\Internal\Wire\WireType;
use Thesis\Protobuf\Type\BoolT;
use Thesis\Protobuf\Type\DoubleT;
use Thesis\Protobuf\Type\EnumT;
use Thesis\Protobuf\Type\Fixed32T;
use Thesis\Protobuf\Type\Fixed64T;
use Thesis\Protobuf\Type\FloatT;
use Thesis\Protobuf\Type\Int32T;
use Thesis\Protobuf\Type\Int64T;
use Thesis\Protobuf\Type\ListT;
use Thesis\Protobuf\Type\MapT;
use Thesis\Protobuf\Type\MessageT;
use Thesis\Protobuf\Type\SFixed32T;
use Thesis\Protobuf\Type\SFixed64T;
use Thesis\Protobuf\Type\SInt32T;
use Thesis\Protobuf\Type\SInt64T;
use Thesis\Protobuf\Type\StringT;
use Thesis\Protobuf\Type\Uint32T;
use Thesis\Protobuf\Type\Uint64T;
use Thesis\Protobuf\Type\Visitor;

/**
 * @internal
 * @template-implements Visitor<WireType>
 */
enum DetermineWireType implements Visitor
{
    case Visitor;

    #[\Override]
    public function bool(BoolT $type): WireType
    {
        return WireType::VARINT;
    }

    #[\Override]
    public function float(FloatT $type): WireType
    {
        return WireType::FIXED32;
    }

    #[\Override]
    public function double(DoubleT $type): WireType
    {
        return WireType::FIXED64;
    }

    #[\Override]
    public function int32(Int32T $type): WireType
    {
        return WireType::VARINT;
    }

    #[\Override]
    public function uint32(Uint32T $type): WireType
    {
        return WireType::VARINT;
    }

    #[\Override]
    public function sint32(SInt32T $type): WireType
    {
        return WireType::VARINT;
    }

    #[\Override]
    public function int64(Int64T $type): WireType
    {
        return WireType::VARINT;
    }

    #[\Override]
    public function uint64(Uint64T $type): WireType
    {
        return WireType::VARINT;
    }

    #[\Override]
    public function sint64(SInt64T $type): WireType
    {
        return WireType::VARINT;
    }

    #[\Override]
    public function fixed32(Fixed32T $type): WireType
    {
        return WireType::FIXED32;
    }

    #[\Override]
    public function sfixed32(SFixed32T $type): WireType
    {
        return WireType::FIXED32;
    }

    #[\Override]
    public function fixed64(Fixed64T $type): WireType
    {
        return WireType::FIXED64;
    }

    #[\Override]
    public function sfixed64(SFixed64T $type): WireType
    {
        return WireType::FIXED64;
    }

    #[\Override]
    public function string(StringT $type): WireType
    {
        return WireType::BYTES;
    }

    #[\Override]
    public function list(ListT $type): WireType
    {
        return $type->element->accept($this);
    }

    #[\Override]
    public function map(MapT $type): WireType
    {
        return WireType::BYTES;
    }

    #[\Override]
    public function enum(EnumT $type): WireType
    {
        return WireType::VARINT;
    }

    #[\Override]
    public function message(MessageT $type): WireType
    {
        return WireType::BYTES;
    }
}
