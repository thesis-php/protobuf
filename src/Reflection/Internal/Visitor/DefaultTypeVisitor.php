<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection\Internal\Visitor;

use Thesis\Protobuf\Reflection\BoolT;
use Thesis\Protobuf\Reflection\BytesT;
use Thesis\Protobuf\Reflection\DoubleT;
use Thesis\Protobuf\Reflection\EnumT;
use Thesis\Protobuf\Reflection\Fixed32T;
use Thesis\Protobuf\Reflection\Fixed64T;
use Thesis\Protobuf\Reflection\FloatT;
use Thesis\Protobuf\Reflection\Int32T;
use Thesis\Protobuf\Reflection\Int64T;
use Thesis\Protobuf\Reflection\ListT;
use Thesis\Protobuf\Reflection\MapT;
use Thesis\Protobuf\Reflection\ObjectT;
use Thesis\Protobuf\Reflection\SFixed32T;
use Thesis\Protobuf\Reflection\SFixed64T;
use Thesis\Protobuf\Reflection\SInt32T;
use Thesis\Protobuf\Reflection\SInt64T;
use Thesis\Protobuf\Reflection\StringT;
use Thesis\Protobuf\Reflection\Type;
use Thesis\Protobuf\Reflection\Uint32T;
use Thesis\Protobuf\Reflection\Uint64T;
use Thesis\Protobuf\Reflection\Visitor;

/**
 * @internal
 * @template TResult
 * @template-implements Visitor<TResult>
 */
abstract class DefaultTypeVisitor implements Visitor
{
    #[\Override]
    public function bool(BoolT $type): mixed
    {
        return $this->default($type);
    }

    #[\Override]
    public function float(FloatT $type): mixed
    {
        return $this->default($type);
    }

    #[\Override]
    public function double(DoubleT $type): mixed
    {
        return $this->default($type);
    }

    #[\Override]
    public function int32(Int32T $type): mixed
    {
        return $this->default($type);
    }

    #[\Override]
    public function uint32(Uint32T $type): mixed
    {
        return $this->default($type);
    }

    #[\Override]
    public function sint32(SInt32T $type): mixed
    {
        return $this->default($type);
    }

    #[\Override]
    public function int64(Int64T $type): mixed
    {
        return $this->default($type);
    }

    #[\Override]
    public function uint64(Uint64T $type): mixed
    {
        return $this->default($type);
    }

    #[\Override]
    public function sint64(SInt64T $type): mixed
    {
        return $this->default($type);
    }

    #[\Override]
    public function fixed32(Fixed32T $type): mixed
    {
        return $this->default($type);
    }

    #[\Override]
    public function sfixed32(SFixed32T $type): mixed
    {
        return $this->default($type);
    }

    #[\Override]
    public function fixed64(Fixed64T $type): mixed
    {
        return $this->default($type);
    }

    #[\Override]
    public function sfixed64(SFixed64T $type): mixed
    {
        return $this->default($type);
    }

    #[\Override]
    public function string(StringT $type): mixed
    {
        return $this->default($type);
    }

    #[\Override]
    public function bytes(BytesT $type): mixed
    {
        return $this->default($type);
    }

    #[\Override]
    public function list(ListT $type): mixed
    {
        return $this->default($type);
    }

    #[\Override]
    public function map(MapT $type): mixed
    {
        return $this->default($type);
    }

    #[\Override]
    public function enum(EnumT $type): mixed
    {
        return $this->default($type);
    }

    #[\Override]
    public function object(ObjectT $type): mixed
    {
        return $this->default($type);
    }

    /**
     * @template T
     * @param Type<T, *, *, *> $type
     * @retrun TResult
     */
    abstract protected function default(Type $type): mixed;
}
