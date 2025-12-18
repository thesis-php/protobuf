<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Type\Visitor;

use Thesis\Protobuf\Type;
use Thesis\Protobuf\Type\BoolT;
use Thesis\Protobuf\Type\DoubleT;
use Thesis\Protobuf\Type\EnumT;
use Thesis\Protobuf\Type\Fixed32T;
use Thesis\Protobuf\Type\Fixed64T;
use Thesis\Protobuf\Type\FloatT;
use Thesis\Protobuf\Type\Int32T;
use Thesis\Protobuf\Type\Int64T;
use Thesis\Protobuf\Type\ListT;
use Thesis\Protobuf\Type\MapT;
use Thesis\Protobuf\Type\MessageT;
use Thesis\Protobuf\Type\SFixed32T;
use Thesis\Protobuf\Type\SFixed64T;
use Thesis\Protobuf\Type\SInt32T;
use Thesis\Protobuf\Type\SInt64T;
use Thesis\Protobuf\Type\StringT;
use Thesis\Protobuf\Type\Uint32T;
use Thesis\Protobuf\Type\Uint64T;
use Thesis\Protobuf\Type\Visitor;

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
    public function message(MessageT $type): mixed
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
