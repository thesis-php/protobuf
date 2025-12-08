<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection\Internal\Visitor;

use BcMath\Number;
use Thesis\Protobuf\Message;
use Thesis\Protobuf\Reflection\Fixed64T;
use Thesis\Protobuf\Reflection\Int32T;
use Thesis\Protobuf\Reflection\Int64T;
use Thesis\Protobuf\Reflection\ListT;
use Thesis\Protobuf\Reflection\MapT;
use Thesis\Protobuf\Reflection\ObjectT;
use Thesis\Protobuf\Reflection\Reflector;
use Thesis\Protobuf\Reflection\SFixed64T;
use Thesis\Protobuf\Reflection\SInt32T;
use Thesis\Protobuf\Reflection\SInt64T;
use Thesis\Protobuf\Reflection\Type;
use Thesis\Protobuf\Reflection\Uint32T;
use Thesis\Protobuf\Reflection\Uint64T;
use function Thesis\Protobuf\Reflection\Internal\selectNumberType;

/**
 * @internal
 * @template-extends DefaultTypeVisitor<mixed>
 */
final class ToValueTypeVisitor extends DefaultTypeVisitor
{
    public function __construct(
        private readonly \ReflectionType $propertyType,
        private readonly Reflector $reflector,
        private readonly mixed $value,
    ) {}

    #[\Override]
    public function int32(Int32T $type): mixed
    {
        return $this->toNumber();
    }

    #[\Override]
    public function uint32(Uint32T $type): mixed
    {
        return $this->toNumber();
    }

    #[\Override]
    public function sint32(SInt32T $type): mixed
    {
        return $this->toNumber();
    }

    #[\Override]
    public function int64(Int64T $type): mixed
    {
        return $this->toNumber();
    }

    #[\Override]
    public function uint64(Uint64T $type): mixed
    {
        return $this->toNumber();
    }

    #[\Override]
    public function sint64(SInt64T $type): mixed
    {
        return $this->toNumber();
    }

    #[\Override]
    public function fixed64(Fixed64T $type): mixed
    {
        return $this->toNumber();
    }

    #[\Override]
    public function sfixed64(SFixed64T $type): mixed
    {
        return $this->toNumber();
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
                    $this->propertyType,
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
                    $this->propertyType,
                    $this->reflector,
                    $key,
                ));

            $mapValue = $type
                ->valueT
                ->accept(new self(
                    $this->propertyType,
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
        return $this->value;
    }

    /**
     * @return Number|int|numeric-string
     */
    private function toNumber(): Number|int|string
    {
        \assert($this->value instanceof Number);

        return match (selectNumberType($this->propertyType)) {
            Number::class => $this->value,
            'int' => (int) $this->value->value,
            'string' => $this->value->value,
        };
    }
}
