<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection\Internal\Visitor;

use Thesis\Protobuf\Reflection\BoolT;
use Thesis\Protobuf\Reflection\BytesT;
use Thesis\Protobuf\Reflection\EnumT;
use Thesis\Protobuf\Reflection\ListT;
use Thesis\Protobuf\Reflection\MapT;
use Thesis\Protobuf\Reflection\ObjectT;
use Thesis\Protobuf\Reflection\StringT;
use Thesis\Protobuf\Reflection\Type;
use function Thesis\Protobuf\Reflection\Internal\zeroed;

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
        return zeroed($this->value);
    }
}
