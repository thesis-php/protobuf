<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Schema\Type\Visitor;

use BcMath\Number;
use Thesis\Endian;
use Thesis\Protobuf\Internal\Schema\Type\BoolT;
use Thesis\Protobuf\Internal\Schema\Type\DoubleT;
use Thesis\Protobuf\Internal\Schema\Type\EnumT;
use Thesis\Protobuf\Internal\Schema\Type\Field;
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
use Thesis\Protobuf\Internal\Serde\AggregateSerializer;
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
use Thesis\Protobuf\Internal\Serde\SerializeEnum;
use Thesis\Protobuf\Internal\Serde\SerializeList;
use Thesis\Protobuf\Internal\Serde\SerializeMap;
use Thesis\Protobuf\Internal\Serde\SerializeMessage;
use Thesis\Protobuf\Internal\Serde\SerializeTag;
use Thesis\Protobuf\Internal\Serde\SerializeValue;
use Thesis\Protobuf\Internal\Tag;
use Thesis\Protobuf\Message;

/**
 * @internal
 * @template T
 * @phpstan-import-type Uint32 from Endian\Order
 * @phpstan-import-type Int32 from Endian\Order
 * @template-implements Visitor<AggregateSerializer<T>>
 */
abstract class DefaultTypeSerializerVisitor implements Visitor
{
    final public function __construct(
        private readonly Tag $tag,
    ) {}

    /**
     * @return SerializeValue<bool>
     */
    #[\Override]
    public function bool(BoolT $type): SerializeValue
    {
        return $this->tagged(SerdeBool::T);
    }

    /**
     * @return SerializeValue<float>
     */
    #[\Override]
    public function float(FloatT $type): SerializeValue
    {
        return $this->tagged(SerdeFloat::T);
    }

    /**
     * @return SerializeValue<float>
     */
    #[\Override]
    public function double(DoubleT $type): SerializeValue
    {
        return $this->tagged(SerdeDouble::T);
    }

    /**
     * @return SerializeValue<Number|int|numeric-string>
     */
    #[\Override]
    public function int32(Int32T $type): SerializeValue
    {
        return $this->tagged(SerdeInt32::T);
    }

    /**
     * @return SerializeValue<Number|int|numeric-string>
     */
    #[\Override]
    public function uint32(Uint32T $type): SerializeValue
    {
        return $this->tagged(SerdeUint32::T);
    }

    /**
     * @return SerializeValue<Number|int|numeric-string>
     */
    #[\Override]
    public function sint32(SInt32T $type): SerializeValue
    {
        return $this->tagged(SerdeSInt32::T);
    }

    /**
     * @return SerializeValue<Number|int|numeric-string>
     */
    #[\Override]
    public function int64(Int64T $type): SerializeValue
    {
        return $this->tagged(SerdeInt64::T);
    }

    /**
     * @return SerializeValue<Number|int|numeric-string>
     */
    #[\Override]
    public function uint64(Uint64T $type): SerializeValue
    {
        return $this->tagged(SerdeUint64::T);
    }

    /**
     * @return SerializeValue<Number|int|numeric-string>
     */
    #[\Override]
    public function sint64(SInt64T $type): SerializeValue
    {
        return $this->tagged(SerdeSInt64::T);
    }

    /**
     * @return SerializeValue<Uint32>
     */
    #[\Override]
    public function fixed32(Fixed32T $type): SerializeValue
    {
        return $this->tagged(SerdeFixed32::T);
    }

    /**
     * @return SerializeValue<Int32>
     */
    #[\Override]
    public function sfixed32(SFixed32T $type): SerializeValue
    {
        return $this->tagged(SerdeSFixed32::T);
    }

    /**
     * @return SerializeValue<Number|int|numeric-string>
     */
    #[\Override]
    public function fixed64(Fixed64T $type): SerializeValue
    {
        return $this->tagged(SerdeFixed64::T);
    }

    /**
     * @return SerializeValue<Number|int|numeric-string>
     */
    #[\Override]
    public function sfixed64(SFixed64T $type): SerializeValue
    {
        return $this->tagged(SerdeSFixed64::T);
    }

    /**
     * @return SerializeValue<non-empty-string>
     */
    #[\Override]
    public function string(StringT $type): SerializeValue
    {
        return $this->tagged(SerdeString::T);
    }

    /**
     * @return SerializeValue<mixed>
     */
    #[\Override]
    public function list(ListT $type): SerializeValue
    {
        /** @phpstan-ignore return.type */
        return new SerializeList(
            $type->element
                ->accept(new ListElementTypeSerializerVisitor($this->tag))
                ->without(SerializeTag::class),
            $this->tag,
            $type->element->accept(new IsPacked()),
        );
    }

    /**
     * @return SerializeValue<array<array-key, mixed>>
     */
    #[\Override]
    public function map(MapT $type): SerializeValue
    {
        $messageT = new MessageT(
            new Field(1, $type->keyT),
            new Field(2, $type->valueT),
        );

        return new SerializeMap(
            new SerializeList(
                $messageT
                    ->accept(new ListElementTypeSerializerVisitor($this->tag))
                    ->without(SerializeTag::class),
                $this->tag,
            ),
            $type,
        );
    }

    /**
     * @return SerializeValue<\BackedEnum>
     */
    #[\Override]
    public function enum(EnumT $type): SerializeValue
    {
        return $this->tagged(SerializeEnum::T);
    }

    /**
     * @return SerializeValue<Message>
     */
    #[\Override]
    public function message(MessageT $type): SerializeValue
    {
        return $this->tagged(SerializeMessage::T);
    }

    /**
     * @template E
     * @no-named-arguments
     * @param SerializeValue<E> ...$serializers
     * @return SerializeValue<E>
     */
    private function tagged(SerializeValue ...$serializers): SerializeValue
    {
        /** @var SerializeTag<E> $tagged */
        $tagged = new SerializeTag($this->tag);

        return new AggregateSerializer(
            $tagged,
            ...$serializers,
        );
    }
}
