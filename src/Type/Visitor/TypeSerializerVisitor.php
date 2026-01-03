<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Type\Visitor;

use Thesis\Endian;
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
 * @template T
 * @phpstan-import-type Uint32 from Endian\Order
 * @phpstan-import-type Int32 from Endian\Order
 * @template-implements Visitor<AggregateSerializer<T>>
 */
final readonly class TypeSerializerVisitor implements Visitor
{
    public function __construct(
        private Tag $tag,
    ) {}

    #[\Override]
    public function bool(BoolT $type): SerializeValue
    {
        return $this->tagged(SerdeBool::T);
    }

    #[\Override]
    public function float(FloatT $type): SerializeValue
    {
        return $this->tagged(SerdeFloat::T);
    }

    #[\Override]
    public function double(DoubleT $type): SerializeValue
    {
        return $this->tagged(SerdeDouble::T);
    }

    #[\Override]
    public function int32(Int32T $type): SerializeValue
    {
        return $this->tagged(SerdeInt32::T);
    }

    #[\Override]
    public function uint32(Uint32T $type): SerializeValue
    {
        return $this->tagged(SerdeUint32::T);
    }

    #[\Override]
    public function sint32(SInt32T $type): SerializeValue
    {
        return $this->tagged(SerdeSInt32::T);
    }

    #[\Override]
    public function int64(Int64T $type): SerializeValue
    {
        return $this->tagged(SerdeInt64::T);
    }

    #[\Override]
    public function uint64(Uint64T $type): SerializeValue
    {
        return $this->tagged(SerdeUint64::T);
    }

    #[\Override]
    public function sint64(SInt64T $type): SerializeValue
    {
        return $this->tagged(SerdeSInt64::T);
    }

    #[\Override]
    public function fixed32(Fixed32T $type): SerializeValue
    {
        return $this->tagged(SerdeFixed32::T);
    }

    #[\Override]
    public function sfixed32(SFixed32T $type): SerializeValue
    {
        return $this->tagged(SerdeSFixed32::T);
    }

    #[\Override]
    public function fixed64(Fixed64T $type): SerializeValue
    {
        return $this->tagged(SerdeFixed64::T);
    }

    #[\Override]
    public function sfixed64(SFixed64T $type): SerializeValue
    {
        return $this->tagged(SerdeSFixed64::T);
    }

    #[\Override]
    public function string(StringT $type): SerializeValue
    {
        return $this->tagged(SerdeString::T);
    }

    #[\Override]
    public function list(ListT $type): SerializeValue
    {
        return $this->aggregated(
            new SerializeList(
                $type
                    ->element
                    ->accept($this)
                    ->without(SerializeTag::class),
                $this->tag,
                $type->packed ?? $type->element->accept(new IsPacked()),
            ),
        );
    }

    #[\Override]
    public function map(MapT $type): SerializeValue
    {
        $messageT = messageT(
            fieldT(1, $type->keyT),
            fieldT(2, $type->valueT),
        );

        return $this->aggregated(
            new SerializeMap(
                new SerializeList(
                    $messageT
                        ->accept($this)
                        ->without(SerializeTag::class),
                    $this->tag,
                ),
                $type,
            ),
        );
    }

    #[\Override]
    public function enum(EnumT $type): SerializeValue
    {
        return $this->tagged(SerializeEnum::T);
    }

    #[\Override]
    public function message(MessageT $type): SerializeValue
    {
        return $this->tagged(SerializeMessage::T);
    }

    /**
     * @template E
     * @no-named-arguments
     * @param SerializeValue<E> ...$serializers
     * @return AggregateSerializer<T>
     */
    private function tagged(SerializeValue ...$serializers): AggregateSerializer
    {
        /** @var SerializeTag<E> $tagged */
        $tagged = new SerializeTag($this->tag);

        return $this->aggregated($tagged, ...$serializers);
    }

    /**
     * @template E
     * @no-named-arguments
     * @param SerializeValue<E> ...$serializers
     * @return AggregateSerializer<T>
     */
    private function aggregated(SerializeValue ...$serializers): AggregateSerializer
    {
        /** @var AggregateSerializer<T> */
        return new AggregateSerializer(...$serializers);
    }
}
