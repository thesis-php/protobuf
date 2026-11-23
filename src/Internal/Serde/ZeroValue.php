<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use BcMath\Number;
use Thesis\Protobuf\Message;
use Thesis\Protobuf\Type\BoolT;
use Thesis\Protobuf\Type\BytesT;
use Thesis\Protobuf\Type\DoubleT;
use Thesis\Protobuf\Type\EnumT;
use Thesis\Protobuf\Type\Fixed32T;
use Thesis\Protobuf\Type\Fixed64T;
use Thesis\Protobuf\Type\FloatT;
use Thesis\Protobuf\Type\GroupT;
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
 * The value a field of the type has when it is absent from the input, as the deserializer of the type returns it:
 * zero, false, an empty string, an empty message. For an enum it is the case for 0 or, failing that, the first
 * case, as protobuf takes the first value of an enum; an enum without cases has no zero value, hence null.
 *
 * Only the key and the value of a map entry need it, so lists, maps and groups, which can be neither, have none.
 *
 * @internal
 * @template-implements Visitor<mixed>
 */
final class ZeroValue implements Visitor
{
    #[\Override]
    public function bool(BoolT $type): bool
    {
        return false;
    }

    #[\Override]
    public function float(FloatT $type): float
    {
        return 0.0;
    }

    #[\Override]
    public function double(DoubleT $type): float
    {
        return 0.0;
    }

    #[\Override]
    public function int32(Int32T $type): int
    {
        return 0;
    }

    #[\Override]
    public function uint32(Uint32T $type): int
    {
        return 0;
    }

    #[\Override]
    public function sint32(SInt32T $type): int
    {
        return 0;
    }

    #[\Override]
    public function int64(Int64T $type): int
    {
        return 0;
    }

    #[\Override]
    public function uint64(Uint64T $type): Number
    {
        return new Number(0);
    }

    #[\Override]
    public function sint64(SInt64T $type): int
    {
        return 0;
    }

    #[\Override]
    public function fixed32(Fixed32T $type): int
    {
        return 0;
    }

    #[\Override]
    public function sfixed32(SFixed32T $type): int
    {
        return 0;
    }

    #[\Override]
    public function fixed64(Fixed64T $type): Number
    {
        return new Number(0);
    }

    #[\Override]
    public function sfixed64(SFixed64T $type): int
    {
        return 0;
    }

    #[\Override]
    public function string(StringT $type): string
    {
        return '';
    }

    #[\Override]
    public function bytes(BytesT $type): string
    {
        return '';
    }

    #[\Override]
    public function list(ListT $type): never
    {
        throw new \LogicException('A list is neither a map key nor a map value.');
    }

    #[\Override]
    public function map(MapT $type): never
    {
        throw new \LogicException('A map is neither a map key nor a map value.');
    }

    #[\Override]
    public function enum(EnumT $type): ?\BackedEnum
    {
        return $type->enum::tryFrom(0) ?? $type->enum::cases()[0] ?? null;
    }

    #[\Override]
    public function message(MessageT $type): Message
    {
        return new Message();
    }

    #[\Override]
    public function group(GroupT $type): never
    {
        throw new \LogicException('A group is neither a map key nor a map value.');
    }
}
