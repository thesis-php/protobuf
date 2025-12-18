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
 * @template T
 * @template-implements Visitor<\Closure(T): Protobuf\Value<T>>
 */
final readonly class ToProtobufValueTypeVisitor implements Visitor
{
    private ToProtobufTypeTypeVisitor $typeVisitor;

    public function __construct(
        private Reflector $reflector,
    ) {
        $this->typeVisitor = new ToProtobufTypeTypeVisitor($reflector);
    }

    #[\Override]
    public function bool(BoolT $type): \Closure
    {
        /** @phpstan-ignore return.type */
        return Protobuf\boolOf(...);
    }

    #[\Override]
    public function float(FloatT $type): \Closure
    {
        /** @phpstan-ignore return.type */
        return Protobuf\floatOf(...);
    }

    #[\Override]
    public function double(DoubleT $type): \Closure
    {
        /** @phpstan-ignore return.type */
        return Protobuf\doubleOf(...);
    }

    #[\Override]
    public function int32(Int32T $type): \Closure
    {
        /** @phpstan-ignore return.type */
        return Protobuf\int32Of(...);
    }

    #[\Override]
    public function uint32(Uint32T $type): \Closure
    {
        /** @phpstan-ignore return.type */
        return Protobuf\uint32Of(...);
    }

    #[\Override]
    public function sint32(SInt32T $type): \Closure
    {
        /** @phpstan-ignore return.type */
        return Protobuf\sint32Of(...);
    }

    #[\Override]
    public function int64(Int64T $type): \Closure
    {
        /** @phpstan-ignore return.type */
        return Protobuf\int64Of(...);
    }

    #[\Override]
    public function uint64(Uint64T $type): \Closure
    {
        /** @phpstan-ignore return.type */
        return Protobuf\uint64Of(...);
    }

    #[\Override]
    public function sint64(SInt64T $type): \Closure
    {
        /** @phpstan-ignore return.type */
        return Protobuf\sint64Of(...);
    }

    #[\Override]
    public function fixed32(Fixed32T $type): \Closure
    {
        /** @phpstan-ignore return.type */
        return Protobuf\fixed32Of(...);
    }

    #[\Override]
    public function sfixed32(SFixed32T $type): \Closure
    {
        /** @phpstan-ignore return.type */
        return Protobuf\sfixed32Of(...);
    }

    #[\Override]
    public function fixed64(Fixed64T $type): \Closure
    {
        /** @phpstan-ignore return.type */
        return Protobuf\fixed64Of(...);
    }

    #[\Override]
    public function sfixed64(SFixed64T $type): \Closure
    {
        /** @phpstan-ignore return.type */
        return Protobuf\sfixed64Of(...);
    }

    #[\Override]
    public function string(StringT $type): \Closure
    {
        /** @phpstan-ignore return.type */
        return Protobuf\stringOf(...);
    }

    #[\Override]
    public function bytes(BytesT $type): \Closure
    {
        /** @phpstan-ignore return.type */
        return Protobuf\bytesOf(...);
    }

    #[\Override]
    public function list(ListT $type): \Closure
    {
        /** @var Type<mixed, 'repeatable'> $element */
        $element = $type->element->accept($this->typeVisitor);

        $mapValue = static fn(array $value): array => $value;

        if ($type->element->accept(new IsObjectType())) {
            $mapper = $type->element->accept(new ToProtobufMessageTypeVisitor($this->reflector));

            $mapValue = static fn(array $value): array => array_map($mapper, $value);
        }

        /** @phpstan-ignore return.type */
        return static fn(array $value) => Protobuf\listOf(
            $element,
            /** @phpstan-ignore argument.type */
            $mapValue($value),
        );
    }

    #[\Override]
    public function map(MapT $type): \Closure
    {
        /** @var Type<mixed, *, 'indexed'> $keyT */
        $keyT = $type->keyT->accept($this->typeVisitor);

        /** @var Type<mixed, *, *, 'map-value'> $valueT */
        $valueT = $type->valueT->accept($this->typeVisitor);

        $mapValue = static fn(Protobuf\Map $value): Protobuf\Map => $value;

        if ($type->valueT->accept(new IsObjectType())) {
            $mapper = $type->valueT->accept(new ToProtobufMessageTypeVisitor($this->reflector));

            $mapValue = static function (Protobuf\Map $map) use ($mapper): Protobuf\Map {
                foreach ($map as $key => $val) {
                    $map[$key] = $mapper($val);
                }

                return $map;
            };
        }

        /** @phpstan-ignore return.type */
        return static fn(Protobuf\Map $value) => Protobuf\mapOf(
            $keyT,
            $valueT,
            $mapValue($value),
        );
    }

    #[\Override]
    public function enum(EnumT $type): \Closure
    {
        /** @phpstan-ignore return.type */
        return Protobuf\enumOf(...);
    }

    #[\Override]
    public function object(ObjectT $type): \Closure
    {
        /** @phpstan-ignore return.type */
        return fn(object $message) => Protobuf\messageOf(
            ...$this
            ->reflector
            ->message($message)
            ->fields,
        );
    }
}
