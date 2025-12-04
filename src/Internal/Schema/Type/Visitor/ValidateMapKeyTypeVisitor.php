<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Schema\Type\Visitor;

use Thesis\Protobuf\Internal\Schema\Type;
use Thesis\Protobuf\Internal\Schema\Type\DoubleT;
use Thesis\Protobuf\Internal\Schema\Type\EnumT;
use Thesis\Protobuf\Internal\Schema\Type\FloatT;
use Thesis\Protobuf\Internal\Schema\Type\ListT;
use Thesis\Protobuf\Internal\Schema\Type\MapT;
use Thesis\Protobuf\Internal\Schema\Type\MessageT;

/**
 * @internal
 * @template-extends DefaultTypeVisitor<null>
 */
final class ValidateMapKeyTypeVisitor extends DefaultTypeVisitor
{
    #[\Override]
    public function float(FloatT $type): never
    {
        throw new \UnexpectedValueException('Key in map fields cannot be float.');
    }

    #[\Override]
    public function double(DoubleT $type): never
    {
        throw new \UnexpectedValueException('Key in map fields cannot be double.');
    }

    #[\Override]
    public function list(ListT $type): never
    {
        throw new \UnexpectedValueException('Key in map fields cannot be list.');
    }

    #[\Override]
    public function map(MapT $type): never
    {
        throw new \UnexpectedValueException('Key in map fields cannot be map.');
    }

    #[\Override]
    public function enum(EnumT $type): never
    {
        throw new \UnexpectedValueException('Key in map fields cannot be enum.');
    }

    #[\Override]
    public function message(MessageT $type): never
    {
        throw new \UnexpectedValueException('Key in map fields cannot be message.');
    }

    #[\Override]
    protected function default(Type $type): null
    {
        return null;
    }
}
