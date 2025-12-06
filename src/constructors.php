<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use BcMath\Number;
use Thesis\Protobuf\Internal\Schema\Type;

/**
 * @api
 * @return Value<bool>
 */
function boolOf(bool $value): Value
{
    return Value::bool($value);
}

const boolT = Type\BoolT::T;

/**
 * @api
 * @param Number|int|numeric-string $num
 * @return Value<Number|int|numeric-string>
 */
function int32Of(Number|int|string $num): Value
{
    return Value::int32($num);
}

const int32T = Type\Int32T::T;

/**
 * @api
 * @param Number|non-negative-int|numeric-string $num
 * @return Value<Number|non-negative-int|numeric-string>
 */
function uint32Of(Number|int|string $num): Value
{
    return Value::uint32($num);
}

const uint32T = Type\Uint32T::T;

/**
 * @api
 * @param Number|int|numeric-string $num
 * @return Value<Number|int|numeric-string>
 */
function sint32Of(Number|int|string $num): Value
{
    return Value::sint32($num);
}

const sint32T = Type\SInt32T::T;

/**
 * @api
 * @param Number|int|numeric-string $num
 * @return Value<Number|int|numeric-string>
 */
function int64Of(Number|int|string $num): Value
{
    return Value::int64($num);
}

const int64T = Type\Int64T::T;

/**
 * @api
 * @param Number|non-negative-int|numeric-string $num
 * @return Value<Number|non-negative-int|numeric-string>
 */
function uint64Of(Number|int|string $num): Value
{
    return Value::uint64($num);
}

const uint64T = Type\Uint64T::T;

/**
 * @api
 * @param Number|int|numeric-string $num
 * @return Value<Number|int|numeric-string>
 */
function sint64Of(Number|int|string $num): Value
{
    return Value::sint64($num);
}

const sint64T = Type\SInt64T::T;

/**
 * @api
 * @param int<0, 4294967295> $num
 * @return Value<int<0, 4294967295>>
 */
function fixed32Of(int $num): Value
{
    return Value::fixed32($num);
}

const fixed32T = Type\Fixed32T::T;

/**
 * @api
 * @param int<-2147483648, 2147483647> $num
 * @return Value<int<-2147483648, 2147483647>>
 */
function sfixed32Of(int $num): Value
{
    return Value::sfixed32($num);
}

const sfixed32T = Type\SFixed32T::T;

/**
 * @api
 * @param Number|int|numeric-string $num
 * @return Value<Number|int|numeric-string>
 */
function fixed64Of(Number|int|string $num): Value
{
    return Value::fixed64($num);
}

const fixed64T = Type\Fixed64T::T;

/**
 * @api
 * @param Number|int|numeric-string $num
 * @return Value<Number|int|numeric-string>
 */
function sfixed64Of(Number|int|string $num): Value
{
    return Value::sfixed64($num);
}

const sfixed64T = Type\SFixed64T::T;

/**
 * @api
 * @return Value<float>
 */
function floatOf(float $num): Value
{
    return Value::float($num);
}

const floatT = Type\FloatT::T;

/**
 * @api
 * @return Value<float>
 */
function doubleOf(float $num): Value
{
    return Value::double($num);
}

const doubleT = Type\DoubleT::T;

/**
 * @api
 * @param non-empty-string $value
 * @return Value<non-empty-string>
 */
function stringOf(string $value): Value
{
    return Value::string($value);
}

const stringT = Type\StringT::T;

/**
 * @api
 * @template T
 * @param positive-int $num
 * @param Value<T> $value
 * @return FieldDescriptor<T>
 */
function fieldOf(int $num, Value $value): FieldDescriptor
{
    return new FieldDescriptor($num, $value);
}

/**
 * @api
 * @template T
 * @param positive-int $num
 * @param Type<T, *, *, *> $type
 * @return Type\Field<T>
 */
function fieldT(int $num, Type $type): Type\Field
{
    return new Type\Field($num, $type);
}

/**
 * @api
 * @no-named-arguments
 * @param FieldDescriptor<*> ...$fields
 */
function message(FieldDescriptor ...$fields): Message
{
    return new Message(...$fields);
}

/**
 * @api
 * @no-named-arguments
 * @param FieldDescriptor<*> ...$fields
 * @return Value<Message>
 */
function messageOf(FieldDescriptor ...$fields): Value
{
    return Value::message(
        new Message(...$fields),
    );
}

/**
 * @api
 * @no-named-arguments
 * @param Type\Field<*> ...$fields
 */
function messageT(Type\Field ...$fields): Type\MessageT
{
    return new Type\MessageT(...$fields);
}

/**
 * @api
 * @template E
 * @param Type<E, 'repeatable', *> $type
 * @param list<E> $values
 * @return Value<list<E>>
 */
function listOf(
    Type $type,
    array $values,
): Value {
    return Value::listOf($type, $values);
}

/**
 * @api
 * @template T
 * @param Type<T, 'repeatable', *, *> $element
 * @return Type\ListT<T>
 */
function listT(Type $element): Type\ListT
{
    return new Type\ListT($element);
}

/**
 * @api
 * @template K
 * @template V
 * @param Type<K, *, 'indexed'> $keyT
 * @param Type<V, *, *> $valueT
 * @param iterable<K, V> $values
 * @return Value<iterable<K, V>>
 * @throws \UnexpectedValueException
 */
function mapOf(
    Type $keyT,
    Type $valueT,
    iterable $values,
): Value {
    return Value::mapOf(
        $keyT,
        $valueT,
        $values,
    );
}

/**
 * @api
 * @template K
 * @template V
 * @param Type<K, *, 'indexed'> $keyT
 * @param Type<V, *, *, 'map-value'> $valueT
 * @return Type\MapT<K, V>
 * @throws \UnexpectedValueException
 */
function mapT(Type $keyT, Type $valueT): Type\MapT
{
    return new Type\MapT($keyT, $valueT);
}

/**
 * @api
 * @return Value<\BackedEnum>
 */
function enumOf(\BackedEnum $enum): Value
{
    return Value::enum($enum);
}

/**
 * @api
 * @param class-string<\BackedEnum> $enum
 */
function enumT(string $enum): Type\EnumT
{
    return new Type\EnumT($enum);
}

/**
 * @api
 * @param Number|int|numeric-string $num
 */
function toNumber(Number|int|string $num): Number
{
    if (!$num instanceof Number) {
        $num = new Number($num);
    }

    return $num;
}
