<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection\Internal\Visitor;

use BcMath\Number;
use Thesis\Protobuf\Reflection\BoolT;
use Thesis\Protobuf\Reflection\BytesT;
use Thesis\Protobuf\Reflection\DoubleT;
use Thesis\Protobuf\Reflection\EnumT;
use Thesis\Protobuf\Reflection\Fixed32T;
use Thesis\Protobuf\Reflection\FloatT;
use Thesis\Protobuf\Reflection\ListT;
use Thesis\Protobuf\Reflection\MapT;
use Thesis\Protobuf\Reflection\ObjectT;
use Thesis\Protobuf\Reflection\SFixed32T;
use Thesis\Protobuf\Reflection\StringT;
use Thesis\Protobuf\Reflection\Type;

/**
 * @internal
 * @template-extends DefaultTypeVisitor<bool>
 */
final class IsValueEmpty extends DefaultTypeVisitor
{
    public function __construct(
        private readonly mixed $value,
    ) {}

    #[\Override]
    public function bool(BoolT $type): bool
    {
        return $this->value === false;
    }

    #[\Override]
    public function float(FloatT $type): bool
    {
        return $this->value === 0.0;
    }

    #[\Override]
    public function double(DoubleT $type): bool
    {
        return $this->value === 0.0;
    }

    #[\Override]
    public function fixed32(Fixed32T $type): bool
    {
        return $this->value === 0;
    }

    #[\Override]
    public function sfixed32(SFixed32T $type): bool
    {
        return $this->value === 0;
    }

    #[\Override]
    public function string(StringT $type): bool
    {
        return $this->value === '';
    }

    #[\Override]
    public function bytes(BytesT $type): bool
    {
        return $this->value === '';
    }

    #[\Override]
    public function list(ListT $type): bool
    {
        return $this->value === [];
    }

    #[\Override]
    public function map(MapT $type): bool
    {
        return $this->value === [];
    }

    #[\Override]
    public function enum(EnumT $type): bool
    {
        if ($this->value instanceof \BackedEnum) {
            return $this->value->value === 0;
        }

        return false;
    }

    #[\Override]
    public function object(ObjectT $type): bool
    {
        return !$this->value instanceof $type->class;
    }

    #[\Override]
    protected function default(Type $type): bool
    {
        if (\is_int($this->value)) {
            return $this->value === 0;
        }

        if (is_numeric($this->value)) {
            return $this->value === '0';
        }

        if ($this->value instanceof Number) {
            return $this->value->value === '0';
        }

        return false;
    }
}
