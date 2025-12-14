<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection\Internal\Visitor;

use Thesis\Protobuf\Map;
use Thesis\Protobuf\Reflection\BoolT;
use Thesis\Protobuf\Reflection\BytesT;
use Thesis\Protobuf\Reflection\DoubleT;
use Thesis\Protobuf\Reflection\EnumT;
use Thesis\Protobuf\Reflection\Fixed32T;
use Thesis\Protobuf\Reflection\FloatT;
use Thesis\Protobuf\Reflection\ListT;
use Thesis\Protobuf\Reflection\MapT;
use Thesis\Protobuf\Reflection\SFixed32T;
use Thesis\Protobuf\Reflection\StringT;
use Thesis\Protobuf\Reflection\Type;

/**
 * @internal
 * @template-extends DefaultTypeVisitor<mixed>
 */
final class ToDefaultValueTypeVisitor extends DefaultTypeVisitor
{
    #[\Override]
    public function bool(BoolT $type): mixed
    {
        return false;
    }

    #[\Override]
    public function float(FloatT $type): mixed
    {
        return 0;
    }

    #[\Override]
    public function double(DoubleT $type): mixed
    {
        return 0;
    }

    #[\Override]
    public function fixed32(Fixed32T $type): mixed
    {
        return 0;
    }

    #[\Override]
    public function sfixed32(SFixed32T $type): mixed
    {
        return 0;
    }

    #[\Override]
    public function string(StringT $type): mixed
    {
        return '';
    }

    #[\Override]
    public function bytes(BytesT $type): mixed
    {
        return '';
    }

    #[\Override]
    public function list(ListT $type): mixed
    {
        return [];
    }

    #[\Override]
    public function map(MapT $type): mixed
    {
        return new Map();
    }

    #[\Override]
    public function enum(EnumT $type): mixed
    {
        $cases = $type->enum::cases();

        foreach ($cases as $case) {
            if ($case->value === 0) {
                return $case;
            }
        }

        return null;
    }

    #[\Override]
    protected function default(Type $type): null
    {
        return null;
    }
}
