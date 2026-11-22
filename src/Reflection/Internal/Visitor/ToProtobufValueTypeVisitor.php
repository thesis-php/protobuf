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
 * Builds the conversion of a property value to a protobuf value once per property: the protobuf type of the value
 * is built once and shared by every value converted, so that the codec of the type is built once too.
 *
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
        $listT = Protobuf\listT($element, $type->packed);

        if ($type->element->accept(new IsObjectType())) {
            $mapper = $type->element->accept(new ToProtobufMessageTypeVisitor($this->reflector));

            /** @phpstan-ignore return.type */
            return static fn(array $value): Protobuf\Value => new Protobuf\Value(array_map($mapper, $value), $listT);
        }

        /** @phpstan-ignore return.type */
        return static fn(array $value): Protobuf\Value => new Protobuf\Value($value, $listT);
    }

    /**
     * A map property holds a Map, an array or any other iterable: the codec of the map reads the entries of any.
     */
    #[\Override]
    public function map(MapT $type): \Closure
    {
        /** @var Type<mixed, *, 'indexed'> $keyT */
        $keyT = $type->keyT->accept($this->typeVisitor);

        /** @var Type<mixed, *, *, 'map-value'> $valueT */
        $valueT = $type->valueT->accept($this->typeVisitor);

        $mapT = Protobuf\mapT($keyT, $valueT);

        if ($type->valueT->accept(new IsObjectType())) {
            $mapper = $type->valueT->accept(new ToProtobufMessageTypeVisitor($this->reflector));

            // The user's map must stay untouched: build a new one with the mapped values. An array keeps its keys,
            // anything else may have keys of any type, which a Map holds.
            $mapValue = static function (iterable $map) use ($mapper): iterable {
                if (!\is_array($map)) {
                    $mapped = new Protobuf\Map();

                    foreach ($map as $key => $val) {
                        $mapped[$key] = $mapper($val);
                    }

                    return $mapped;
                }

                return array_map($mapper, $map);
            };

            /** @phpstan-ignore return.type */
            return static fn(iterable $value): Protobuf\Value => new Protobuf\Value($mapValue($value), $mapT);
        }

        /** @phpstan-ignore return.type */
        return static fn(iterable $value): Protobuf\Value => new Protobuf\Value($value, $mapT);
    }

    #[\Override]
    public function enum(EnumT $type): \Closure
    {
        $enumT = Protobuf\enumT($type->enum);

        /** @phpstan-ignore return.type */
        return static fn(\BackedEnum $value): Protobuf\Value => new Protobuf\Value($value, $enumT);
    }

    #[\Override]
    public function object(ObjectT $type): \Closure
    {
        $reflector = $this->reflector;
        $messageT = $reflector->type($type->class);

        /** @phpstan-ignore return.type */
        return static fn(object $message): Protobuf\Value => new Protobuf\Value($reflector->message($message), $messageT);
    }
}
