<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection\Internal\Visitor;

use Thesis\Protobuf\Reflection\BoolT;
use Thesis\Protobuf\Reflection\BytesT;
use Thesis\Protobuf\Reflection\DoubleT;
use Thesis\Protobuf\Reflection\EnumT;
use Thesis\Protobuf\Reflection\FloatT;
use Thesis\Protobuf\Reflection\ListT;
use Thesis\Protobuf\Reflection\MapT;
use Thesis\Protobuf\Reflection\ObjectT;
use Thesis\Protobuf\Reflection\StringT;
use Thesis\Protobuf\Reflection\Type;
use function Thesis\Protobuf\Reflection\Internal\zeroed;

/**
 * Builds the check whether a value of the type is its default value, which is not written, once per type.
 *
 * @internal
 * @template-extends DefaultTypeVisitor<\Closure(mixed): bool>
 */
final class IsValueEmpty extends DefaultTypeVisitor
{
    #[\Override]
    public function bool(BoolT $type): \Closure
    {
        return static fn(mixed $value): bool => $value === false;
    }

    #[\Override]
    public function float(FloatT $type): \Closure
    {
        return self::isPositiveZero(...);
    }

    #[\Override]
    public function double(DoubleT $type): \Closure
    {
        return self::isPositiveZero(...);
    }

    #[\Override]
    public function string(StringT $type): \Closure
    {
        return static fn(mixed $value): bool => $value === '';
    }

    #[\Override]
    public function bytes(BytesT $type): \Closure
    {
        return static fn(mixed $value): bool => $value === '';
    }

    #[\Override]
    public function list(ListT $type): \Closure
    {
        return static fn(mixed $value): bool => $value === [];
    }

    #[\Override]
    public function map(MapT $type): \Closure
    {
        return static fn(mixed $value): bool => $value === [];
    }

    #[\Override]
    public function enum(EnumT $type): \Closure
    {
        return static fn(mixed $value): bool => $value instanceof \BackedEnum && $value->value === 0;
    }

    #[\Override]
    public function object(ObjectT $type): \Closure
    {
        $class = $type->class;

        return static fn(mixed $value): bool => !$value instanceof $class;
    }

    /**
     * @return \Closure(mixed): bool
     */
    #[\Override]
    protected function default(Type $type): \Closure
    {
        return zeroed(...);
    }

    /**
     * Only +0.0 is the default value: -0.0 differs from it in the sign bit and must be encoded.
     * An int 0 (a property typed int|float) is the default value too.
     */
    private static function isPositiveZero(mixed $value): bool
    {
        return $value === 0 || ($value === 0.0 && fdiv(1, $value) === INF);
    }
}
