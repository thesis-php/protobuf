<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection\Internal\Visitor;

use BcMath\Number;
use Thesis\Protobuf\Message;
use Thesis\Protobuf\Reflection\BoolT;
use Thesis\Protobuf\Reflection\BytesT;
use Thesis\Protobuf\Reflection\DoubleT;
use Thesis\Protobuf\Reflection\EnumT;
use Thesis\Protobuf\Reflection\Fixed32T;
use Thesis\Protobuf\Reflection\FloatT;
use Thesis\Protobuf\Reflection\Int32T;
use Thesis\Protobuf\Reflection\ListT;
use Thesis\Protobuf\Reflection\MapT;
use Thesis\Protobuf\Reflection\ObjectT;
use Thesis\Protobuf\Reflection\Reflector;
use Thesis\Protobuf\Reflection\SFixed32T;
use Thesis\Protobuf\Reflection\SInt32T;
use Thesis\Protobuf\Reflection\StringT;
use Thesis\Protobuf\Reflection\Type;
use Thesis\Protobuf\Reflection\Uint32T;

/**
 * @internal
 * @template-extends DefaultTypeVisitor<mixed>
 */
final class ToValueTypeVisitor extends DefaultTypeVisitor
{
    public function __construct(
        private readonly Reflector $reflector,
        private readonly mixed $value,
    ) {}

    #[\Override]
    public function bool(BoolT $type): mixed
    {
        \assert(\is_bool($this->value));

        return $this->value;
    }

    #[\Override]
    public function float(FloatT $type): mixed
    {
        \assert(\is_float($this->value));

        return $this->value;
    }

    #[\Override]
    public function double(DoubleT $type): mixed
    {
        \assert(\is_float($this->value));

        return $this->value;
    }

    #[\Override]
    public function int32(Int32T $type): mixed
    {
        \assert(\is_int($this->value));

        return $this->value;
    }

    #[\Override]
    public function uint32(Uint32T $type): mixed
    {
        \assert(\is_int($this->value));

        return $this->value;
    }

    #[\Override]
    public function sint32(SInt32T $type): mixed
    {
        \assert(\is_int($this->value));

        return $this->value;
    }


    #[\Override]
    public function fixed32(Fixed32T $type): mixed
    {
        \assert(\is_int($this->value));

        return $this->value;
    }

    #[\Override]
    public function sfixed32(SFixed32T $type): mixed
    {
        \assert(\is_int($this->value));

        return $this->value;
    }

    #[\Override]
    public function string(StringT $type): mixed
    {
        \assert(\is_string($this->value));

        return $this->value;
    }

    #[\Override]
    public function bytes(BytesT $type): mixed
    {
        \assert(\is_string($this->value));

        return $this->value;
    }

    #[\Override]
    public function enum(EnumT $type): mixed
    {
        \assert($this->value instanceof \BackedEnum);

        return $this->value;
    }

    #[\Override]
    public function list(ListT $type): mixed
    {
        \assert(\is_array($this->value) && array_is_list($this->value));

        $list = [];

        foreach ($this->value as $value) {
            $list[] = $type
                ->element
                ->accept(new self(
                    $this->reflector,
                    $value,
                ));
        }

        return $list;
    }

    #[\Override]
    public function map(MapT $type): mixed
    {
        \assert(is_iterable($this->value));

        $map = [];

        foreach ($this->value as $key => $value) {
            /** @var array-key $mapKey */
            $mapKey = $type
                ->keyT
                ->accept(new self(
                    $this->reflector,
                    $key,
                ));

            $mapValue = $type
                ->valueT
                ->accept(new self(
                    $this->reflector,
                    $value,
                ));

            $map[$mapKey] = $mapValue;
        }

        return $map;
    }

    #[\Override]
    public function object(ObjectT $type): mixed
    {
        \assert($this->value instanceof Message);

        return $this->reflector->map($this->value, $type->class);
    }

    #[\Override]
    protected function default(Type $type): mixed
    {
        \assert($this->value instanceof Number);

        return $this->value;
    }
}
