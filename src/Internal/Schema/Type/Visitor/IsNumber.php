<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Schema\Type\Visitor;

use Thesis\Protobuf\Internal\Schema\Type;
use Thesis\Protobuf\Internal\Schema\Type\Fixed64T;
use Thesis\Protobuf\Internal\Schema\Type\Int32T;
use Thesis\Protobuf\Internal\Schema\Type\Int64T;
use Thesis\Protobuf\Internal\Schema\Type\SFixed64T;
use Thesis\Protobuf\Internal\Schema\Type\SInt32T;
use Thesis\Protobuf\Internal\Schema\Type\SInt64T;
use Thesis\Protobuf\Internal\Schema\Type\Uint32T;
use Thesis\Protobuf\Internal\Schema\Type\Uint64T;

/**
 * @internal
 * @template-extends DefaultTypeVisitor<bool>
 */
final class IsNumber extends DefaultTypeVisitor
{
    #[\Override]
    public function int32(Int32T $type): bool
    {
        return true;
    }

    #[\Override]
    public function uint32(Uint32T $type): bool
    {
        return true;
    }

    #[\Override]
    public function sint32(SInt32T $type): bool
    {
        return true;
    }

    #[\Override]
    public function int64(Int64T $type): bool
    {
        return true;
    }

    #[\Override]
    public function uint64(Uint64T $type): bool
    {
        return true;
    }

    #[\Override]
    public function sint64(SInt64T $type): bool
    {
        return true;
    }

    #[\Override]
    public function fixed64(Fixed64T $type): bool
    {
        return true;
    }

    #[\Override]
    public function sfixed64(SFixed64T $type): bool
    {
        return true;
    }

    #[\Override]
    protected function default(Type $type): bool
    {
        return false;
    }
}
