<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Schema\Type\Visitor;

use Thesis\Protobuf\Internal\Schema\Type;
use Thesis\Protobuf\Internal\Schema\Type\ListT;
use Thesis\Protobuf\Internal\Schema\Type\MapT;

/**
 * @internal
 * @template-extends DefaultTypeVisitor<null>
 */
final class ValidateMapValueTypeVisitor extends DefaultTypeVisitor
{
    #[\Override]
    public function list(ListT $type): never
    {
        throw new \UnexpectedValueException('Value in map fields cannot be list.');
    }

    #[\Override]
    public function map(MapT $type): never
    {
        throw new \UnexpectedValueException('Value in map fields cannot be map.');
    }

    #[\Override]
    protected function default(Type $type): null
    {
        return null;
    }
}
