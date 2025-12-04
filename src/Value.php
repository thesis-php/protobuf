<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use BcMath\Number;
use Thesis\Endian;
use Thesis\Protobuf\Internal\Schema\Type;

/**
 * @api
 * @phpstan-import-type Int32 from Endian\Order
 * @phpstan-import-type Uint32 from Endian\Order
 * @template-covariant T
 */
final readonly class Value
{
    /**
     * @return self<bool>
     */
    public static function bool(bool $value): self
    {
        return new self($value, boolT);
    }

    /**
     * @param Number|int|numeric-string $num
     * @return self<Number>
     */
    public static function int32(Number|int|string $num): self
    {
        return new self(toNumber($num), int32T);
    }

    /**
     * @param Number|int|numeric-string $num
     * @return self<Number>
     */
    public static function int64(Number|int|string $num): self
    {
        return new self(toNumber($num), int64T);
    }

    /**
     * @param Number|non-negative-int|numeric-string $num
     * @return self<Number>
     */
    public static function uint32(Number|int|string $num): self
    {
        return new self(toNumber($num), uint32T);
    }

    /**
     * @param Number|non-negative-int|numeric-string $num
     * @return self<Number>
     */
    public static function uint64(Number|int|string $num): self
    {
        return new self(toNumber($num), uint64T);
    }

    /**
     * @param Number|int|numeric-string $num
     * @return self<Number>
     */
    public static function sint32(Number|int|string $num): self
    {
        return new self(toNumber($num), sint32T);
    }

    /**
     * @param Number|int|numeric-string $num
     * @return self<Number>
     */
    public static function sint64(Number|int|string $num): self
    {
        return new self(toNumber($num), sint64T);
    }

    /**
     * @param Uint32 $num
     * @return self<Uint32>
     */
    public static function fixed32(int $num): self
    {
        return new self($num, fixed32T);
    }

    /**
     * @param Int32 $num
     * @return self<Int32>
     */
    public static function sfixed32(int $num): self
    {
        return new self($num, sfixed32T);
    }

    /**
     * @param Number|int|numeric-string $num
     * @return self<Number>
     */
    public static function fixed64(Number|int|string $num): self
    {
        return new self(toNumber($num), fixed64T);
    }

    /**
     * @param Number|int|numeric-string $num
     * @return self<Number>
     */
    public static function sfixed64(Number|int|string $num): self
    {
        return new self(toNumber($num), sfixed64T);
    }

    /**
     * @return self<float>
     */
    public static function float(float $num): self
    {
        return new self($num, floatT);
    }

    /**
     * @return self<double>
     */
    public static function double(float $num): self
    {
        return new self($num, doubleT);
    }

    /**
     * @param non-empty-string $value
     * @return self<non-empty-string>
     */
    public static function string(string $value): self
    {
        return new self($value, stringT);
    }

    /**
     * @return self<\BackedEnum>
     */
    public static function enum(\BackedEnum $enum): self
    {
        return new self(
            $enum,
            enumT($enum::class),
        );
    }

    /**
     * @return self<Message>
     */
    public static function message(Message $message): self
    {
        return new self(
            $message,
            $message->type(),
        );
    }

    /**
     * @template E
     * @param Type<E> $type
     * @param list<E> $values
     * @return self<list<E>>
     */
    public static function listOf(
        Type $type,
        array $values,
    ): self {
        return new self(
            $values,
            listT($type),
        );
    }

    /**
     * @template K of array-key
     * @template V
     * @param Type<K> $keyT
     * @param Type<V> $valueT
     * @param array<K, V> $values
     * @return self<list<Message>>
     */
    public static function mapOf(
        Type $keyT,
        Type $valueT,
        array $values,
    ): self {
        return self::listOf(
            messageT(
                fieldT(1, $keyT),
                fieldT(2, $valueT),
            ),
            array_map(
                static fn(mixed $key, mixed $value) => message(
                    fieldOf(1, new self($key, $keyT)),
                    fieldOf(2, new self($value, $valueT)),
                ),
                array_keys($values),
                array_values($values),
            ),
        );
    }

    /**
     * @param T $value
     * @param Type<T> $type
     */
    private function __construct(
        public mixed $value,
        public Type $type,
    ) {}
}
