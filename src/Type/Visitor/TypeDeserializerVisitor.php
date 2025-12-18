<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Type\Visitor;

use Thesis\Protobuf\Internal\Serde\DeserializeEnum;
use Thesis\Protobuf\Internal\Serde\DeserializeList;
use Thesis\Protobuf\Internal\Serde\DeserializeMap;
use Thesis\Protobuf\Internal\Serde\DeserializeMessage;
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
use Thesis\Protobuf\Internal\Wire\Tag;
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
use function Thesis\Protobuf\fieldT;
use function Thesis\Protobuf\messageT;

/**
 * @internal
 * @template-implements Visitor<DeserializeValue<*>>
 */
final readonly class TypeDeserializerVisitor implements Visitor
{
    public function __construct(
        private Tag $tag,
    ) {}

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
    public function list(ListT $type): DeserializeValue
    {
        return new DeserializeList(
            /** @phpstan-ignore argument.type */
            $type->element->accept($this),
            $this->tag,
            $type->element->accept(new IsPacked()),
        );
    }

    #[\Override]
    public function map(MapT $type): DeserializeValue
    {
        $messageT = messageT(
            fieldT(1, $type->keyT),
            fieldT(2, $type->valueT),
        );

        return new DeserializeMap(
            /** @phpstan-ignore argument.type */
            new DeserializeList(
                /** @phpstan-ignore argument.type */
                $messageT->accept($this),
                $this->tag,
            ),
        );
    }

    #[\Override]
    public function enum(EnumT $type): DeserializeValue
    {
        return new DeserializeEnum($type->enum);
    }

    #[\Override]
    public function message(MessageT $type): DeserializeValue
    {
        return new DeserializeMessage($type);
    }
}
