<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Protobuf\Type;
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
use Thesis\Protobuf\Type\Visitor\IsPackable;
use Thesis\Protobuf\WireType;

/**
 * Builds the codec of a field type once and keeps it for as long as the type object lives.
 *
 * A message codec does not build the codecs of the message's fields, MessageFields does it when the first message
 * of the type is read or written: a recursive message type is not complete while its codec is being built.
 *
 * @internal
 * @template-implements Visitor<FieldCodec>
 */
final class FieldCodecs implements Visitor
{
    /** @var ?\WeakMap<Type<*, *, *, *>, FieldCodec> */
    private static ?\WeakMap $codecs = null;

    private static ?self $visitor = null;

    private function __construct() {}

    /**
     * @param Type<*, *, *, *> $type
     */
    public static function of(Type $type): FieldCodec
    {
        $codecs = self::$codecs ??= new \WeakMap();

        return $codecs[$type] ??= $type->accept(self::$visitor ??= new self());
    }

    #[\Override]
    public function bool(BoolT $type): FieldCodec
    {
        return new SingularCodec(SerdeBool::T, SerdeBool::T, WireType::VARINT);
    }

    #[\Override]
    public function float(FloatT $type): FieldCodec
    {
        return new SingularCodec(SerdeFloat::T, SerdeFloat::T, WireType::FIXED32);
    }

    #[\Override]
    public function double(DoubleT $type): FieldCodec
    {
        return new SingularCodec(SerdeDouble::T, SerdeDouble::T, WireType::FIXED64);
    }

    #[\Override]
    public function int32(Int32T $type): FieldCodec
    {
        return new SingularCodec(SerdeInt32::T, SerdeInt32::T, WireType::VARINT);
    }

    #[\Override]
    public function uint32(Uint32T $type): FieldCodec
    {
        return new SingularCodec(SerdeUint32::T, SerdeUint32::T, WireType::VARINT);
    }

    #[\Override]
    public function sint32(SInt32T $type): FieldCodec
    {
        return new SingularCodec(SerdeSInt32::T, SerdeSInt32::T, WireType::VARINT);
    }

    #[\Override]
    public function int64(Int64T $type): FieldCodec
    {
        return new SingularCodec(SerdeInt64::T, SerdeInt64::T, WireType::VARINT);
    }

    #[\Override]
    public function uint64(Uint64T $type): FieldCodec
    {
        return new SingularCodec(SerdeUint64::T, SerdeUint64::T, WireType::VARINT);
    }

    #[\Override]
    public function sint64(SInt64T $type): FieldCodec
    {
        return new SingularCodec(SerdeSInt64::T, SerdeSInt64::T, WireType::VARINT);
    }

    #[\Override]
    public function fixed32(Fixed32T $type): FieldCodec
    {
        return new SingularCodec(SerdeFixed32::T, SerdeFixed32::T, WireType::FIXED32);
    }

    #[\Override]
    public function sfixed32(SFixed32T $type): FieldCodec
    {
        return new SingularCodec(SerdeSFixed32::T, SerdeSFixed32::T, WireType::FIXED32);
    }

    #[\Override]
    public function fixed64(Fixed64T $type): FieldCodec
    {
        return new SingularCodec(SerdeFixed64::T, SerdeFixed64::T, WireType::FIXED64);
    }

    #[\Override]
    public function sfixed64(SFixed64T $type): FieldCodec
    {
        return new SingularCodec(SerdeSFixed64::T, SerdeSFixed64::T, WireType::FIXED64);
    }

    #[\Override]
    public function string(StringT $type): FieldCodec
    {
        return new SingularCodec(SerdeString::T, SerdeString::T, WireType::BYTES);
    }

    #[\Override]
    public function list(ListT $type): FieldCodec
    {
        // ListT rejects lists and maps as elements, so the element is a scalar, an enum or a message.
        /** @var SingularCodec<mixed> $element */
        $element = self::of($type->element);

        $packable = $type->element->accept(new IsPackable());

        return new ListCodec($element, $type->packed ?? $packable, $packable);
    }

    #[\Override]
    public function map(MapT $type): FieldCodec
    {
        $zero = new ZeroValue();

        return new MapCodec(
            self::of($type->keyT),
            self::of($type->valueT),
            $type->keyT->accept($zero),
            $type->valueT->accept($zero),
        );
    }

    #[\Override]
    public function enum(EnumT $type): FieldCodec
    {
        return new SingularCodec(SerializeEnum::T, new DeserializeEnum($type->enum), WireType::VARINT);
    }

    #[\Override]
    public function message(MessageT $type): FieldCodec
    {
        return new SingularCodec(SerializeMessage::T, new DeserializeMessage($type), WireType::BYTES);
    }
}
