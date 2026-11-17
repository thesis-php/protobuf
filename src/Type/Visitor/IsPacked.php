<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Type\Visitor;

use Thesis\Protobuf\Type;
use Thesis\Protobuf\Type\BoolT;
use Thesis\Protobuf\Type\DoubleT;
use Thesis\Protobuf\Type\EnumT;
use Thesis\Protobuf\Type\Fixed32T;
use Thesis\Protobuf\Type\Fixed64T;
use Thesis\Protobuf\Type\FloatT;
use Thesis\Protobuf\Type\Int32T;
use Thesis\Protobuf\Type\Int64T;
use Thesis\Protobuf\Type\SFixed32T;
use Thesis\Protobuf\Type\SFixed64T;
use Thesis\Protobuf\Type\SInt32T;
use Thesis\Protobuf\Type\SInt64T;
use Thesis\Protobuf\Type\Uint32T;
use Thesis\Protobuf\Type\Uint64T;

/**
 * Whether repeated elements of the type can be packed: only scalar numeric, bool and enum types can.
 * Any other type, including one added later, is not packable unless listed here.
 *
 * @template-extends DefaultTypeVisitor<bool>
 */
final class IsPacked extends DefaultTypeVisitor
{
    #[\Override]
    public function bool(BoolT $type): bool
    {
        return true;
    }

    #[\Override]
    public function float(FloatT $type): bool
    {
        return true;
    }

    #[\Override]
    public function double(DoubleT $type): bool
    {
        return true;
    }

    #[\Override]
    public function int32(Int32T $type): bool
    {
        return true;
    }

    #[\Override]
    public function uint32(Uint32T $type): bool
    {
        return true;
    }

    #[\Override]
    public function sint32(SInt32T $type): bool
    {
        return true;
    }

    #[\Override]
    public function int64(Int64T $type): bool
    {
        return true;
    }

    #[\Override]
    public function uint64(Uint64T $type): bool
    {
        return true;
    }

    #[\Override]
    public function sint64(SInt64T $type): bool
    {
        return true;
    }

    #[\Override]
    public function fixed32(Fixed32T $type): bool
    {
        return true;
    }

    #[\Override]
    public function sfixed32(SFixed32T $type): bool
    {
        return true;
    }

    #[\Override]
    public function fixed64(Fixed64T $type): bool
    {
        return true;
    }

    #[\Override]
    public function sfixed64(SFixed64T $type): bool
    {
        return true;
    }

    #[\Override]
    public function enum(EnumT $type): bool
    {
        return true;
    }

    #[\Override]
    protected function default(Type $type): bool
    {
        return false;
    }
}
