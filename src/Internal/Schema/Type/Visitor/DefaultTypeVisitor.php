<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Schema\Type\Visitor;

use Thesis\Protobuf\Internal\Schema\Type;
use Thesis\Protobuf\Internal\Schema\Type\BoolT;
use Thesis\Protobuf\Internal\Schema\Type\DoubleT;
use Thesis\Protobuf\Internal\Schema\Type\EnumT;
use Thesis\Protobuf\Internal\Schema\Type\Fixed32T;
use Thesis\Protobuf\Internal\Schema\Type\Fixed64T;
use Thesis\Protobuf\Internal\Schema\Type\FloatT;
use Thesis\Protobuf\Internal\Schema\Type\Int32T;
use Thesis\Protobuf\Internal\Schema\Type\Int64T;
use Thesis\Protobuf\Internal\Schema\Type\ListT;
use Thesis\Protobuf\Internal\Schema\Type\MapT;
use Thesis\Protobuf\Internal\Schema\Type\MessageT;
use Thesis\Protobuf\Internal\Schema\Type\SFixed32T;
use Thesis\Protobuf\Internal\Schema\Type\SFixed64T;
use Thesis\Protobuf\Internal\Schema\Type\SInt32T;
use Thesis\Protobuf\Internal\Schema\Type\SInt64T;
use Thesis\Protobuf\Internal\Schema\Type\StringT;
use Thesis\Protobuf\Internal\Schema\Type\Uint32T;
use Thesis\Protobuf\Internal\Schema\Type\Uint64T;
use Thesis\Protobuf\Internal\Schema\Type\Visitor;

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
     * @param Type<T> $type
     * @retrun TResult
     */
    abstract protected function default(Type $type): mixed;
}
