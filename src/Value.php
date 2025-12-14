<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use BcMath\Number;
use Thesis\Protobuf\Internal\Schema\Type;

/**
 * @api
 * @template T
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
     * @return self<int>
     */
    public static function int32(int $num): self
    {
        return new self($num, int32T);
    }

    /**
     * @return self<Number>
     */
    public static function int64(Number $num): self
    {
        return new self($num, int64T);
    }

    /**
     * @return self<int>
     */
    public static function uint32(int $num): self
    {
        return new self($num, uint32T);
    }

    /**
     * @return self<Number>
     */
    public static function uint64(Number $num): self
    {
        return new self($num, uint64T);
    }

    /**
     * @return self<int>
     */
    public static function sint32(int $num): self
    {
        return new self($num, sint32T);
    }

    /**
     * @return self<Number>
     */
    public static function sint64(Number $num): self
    {
        return new self($num, sint64T);
    }

    /**
     * @return self<int>
     */
    public static function fixed32(int $num): self
    {
        return new self($num, fixed32T);
    }

    /**
     * @return self<int>
     */
    public static function sfixed32(int $num): self
    {
        return new self($num, sfixed32T);
    }

    /**
     * @return self<Number>
     */
    public static function fixed64(Number $num): self
    {
        return new self($num, fixed64T);
    }

    /**
     * @return self<Number>
     */
    public static function sfixed64(Number $num): self
    {
        return new self($num, sfixed64T);
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
     * @return self<string>
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
     * @param Type<E, 'repeatable', *, *> $type
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
     * @template K
     * @template V
     * @param Type<K, *, 'indexed'> $keyT
     * @param Type<V, *, *, 'map-value'> $valueT
     * @param iterable<K, V> $values
     * @return self<iterable<K, V>>
     * @throws \UnexpectedValueException
     */
    public static function mapOf(
        Type $keyT,
        Type $valueT,
        iterable $values,
    ): self {
        return new self(
            $values,
            mapT($keyT, $valueT),
        );
    }

    /**
     * @internal
     * @param T $value
     * @param Type<T, *, *, *> $type
     */
    public function __construct(
        public mixed $value,
        public Type $type,
    ) {}
}
