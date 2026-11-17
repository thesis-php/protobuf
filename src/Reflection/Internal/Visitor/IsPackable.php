<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection\Internal\Visitor;

use Thesis\Protobuf\Reflection\BoolT;
use Thesis\Protobuf\Reflection\DoubleT;
use Thesis\Protobuf\Reflection\EnumT;
use Thesis\Protobuf\Reflection\Fixed32T;
use Thesis\Protobuf\Reflection\Fixed64T;
use Thesis\Protobuf\Reflection\FloatT;
use Thesis\Protobuf\Reflection\Int32T;
use Thesis\Protobuf\Reflection\Int64T;
use Thesis\Protobuf\Reflection\SFixed32T;
use Thesis\Protobuf\Reflection\SFixed64T;
use Thesis\Protobuf\Reflection\SInt32T;
use Thesis\Protobuf\Reflection\SInt64T;
use Thesis\Protobuf\Reflection\Type;
use Thesis\Protobuf\Reflection\Uint32T;
use Thesis\Protobuf\Reflection\Uint64T;

/**
 * Whether repeated elements of the type can be packed: only scalar numeric, bool and enum types can.
 * Any other type, including one added later, is not packable unless listed here.
 *
 * @internal
 * @template-extends DefaultTypeVisitor<bool>
 */
final class IsPackable extends DefaultTypeVisitor
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
