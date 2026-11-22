<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection\Internal\Visitor;

use BcMath\Number;
use Thesis\Protobuf\Map;
use Thesis\Protobuf\Reflection\BoolT;
use Thesis\Protobuf\Reflection\BytesT;
use Thesis\Protobuf\Reflection\DoubleT;
use Thesis\Protobuf\Reflection\EnumT;
use Thesis\Protobuf\Reflection\Fixed64T;
use Thesis\Protobuf\Reflection\FloatT;
use Thesis\Protobuf\Reflection\Internal\Api\ExpectedPhpType;
use Thesis\Protobuf\Reflection\ListT;
use Thesis\Protobuf\Reflection\MapT;
use Thesis\Protobuf\Reflection\ObjectT;
use Thesis\Protobuf\Reflection\StringT;
use Thesis\Protobuf\Reflection\Type;
use Thesis\Protobuf\Reflection\Uint64T;

/**
 * The PHP types that hold the values of a protobuf type, as the decoder produces them.
 *
 * @internal
 * @template-extends DefaultTypeVisitor<ExpectedPhpType>
 */
final class ToExpectedPhpType extends DefaultTypeVisitor
{
    #[\Override]
    public function bool(BoolT $type): ExpectedPhpType
    {
        return new ExpectedPhpType(['bool', 'true', 'false']);
    }

    #[\Override]
    public function float(FloatT $type): ExpectedPhpType
    {
        return new ExpectedPhpType(['float']);
    }

    #[\Override]
    public function double(DoubleT $type): ExpectedPhpType
    {
        return new ExpectedPhpType(['float']);
    }

    #[\Override]
    public function uint64(Uint64T $type): ExpectedPhpType
    {
        return new ExpectedPhpType(class: Number::class);
    }

    #[\Override]
    public function fixed64(Fixed64T $type): ExpectedPhpType
    {
        return new ExpectedPhpType(class: Number::class);
    }

    #[\Override]
    public function string(StringT $type): ExpectedPhpType
    {
        return new ExpectedPhpType(['string']);
    }

    #[\Override]
    public function bytes(BytesT $type): ExpectedPhpType
    {
        return new ExpectedPhpType(['string']);
    }

    #[\Override]
    public function list(ListT $type): ExpectedPhpType
    {
        return new ExpectedPhpType(['array', 'iterable']);
    }

    #[\Override]
    public function map(MapT $type): ExpectedPhpType
    {
        // An array property is let through as before: whether it may hold a map is a decision of its own.
        return new ExpectedPhpType(['array', 'iterable'], Map::class);
    }

    #[\Override]
    public function enum(EnumT $type): ExpectedPhpType
    {
        return new ExpectedPhpType(class: $type->enum);
    }

    #[\Override]
    public function object(ObjectT $type): ExpectedPhpType
    {
        return new ExpectedPhpType(class: $type->class);
    }

    /**
     * The integer types other than uint64 and fixed64.
     */
    #[\Override]
    protected function default(Type $type): ExpectedPhpType
    {
        return new ExpectedPhpType(['int']);
    }
}
