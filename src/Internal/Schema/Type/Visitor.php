<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Schema\Type;

/**
 * @internal
 * @template-covariant TResult
 */
interface Visitor
{
    /**
     * @return TResult
     */
    public function bool(BoolT $type): mixed;

    /**
     * @return TResult
     */
    public function float(FloatT $type): mixed;

    /**
     * @return TResult
     */
    public function double(DoubleT $type): mixed;

    /**
     * @return TResult
     */
    public function int32(Int32T $type): mixed;

    /**
     * @return TResult
     */
    public function uint32(Uint32T $type): mixed;

    /**
     * @return TResult
     */
    public function sint32(SInt32T $type): mixed;

    /**
     * @return TResult
     */
    public function int64(Int64T $type): mixed;

    /**
     * @return TResult
     */
    public function uint64(Uint64T $type): mixed;

    /**
     * @return TResult
     */
    public function sint64(SInt64T $type): mixed;

    /**
     * @return TResult
     */
    public function fixed32(Fixed32T $type): mixed;

    /**
     * @return TResult
     */
    public function sfixed32(SFixed32T $type): mixed;

    /**
     * @return TResult
     */
    public function fixed64(Fixed64T $type): mixed;

    /**
     * @return TResult
     */
    public function sfixed64(SFixed64T $type): mixed;

    /**
     * @return TResult
     */
    public function string(StringT $type): mixed;

    /**
     * @template T
     * @param ListT<T> $type
     * @return TResult
     */
    public function list(ListT $type): mixed;

    /**
     * @template K of array-key
     * @template V
     * @param MapT<K, V> $type
     * @return TResult
     */
    public function map(MapT $type): mixed;

    /**
     * @return TResult
     */
    public function enum(EnumT $type): mixed;

    /**
     * @return TResult
     */
    public function message(MessageT $type): mixed;
}
