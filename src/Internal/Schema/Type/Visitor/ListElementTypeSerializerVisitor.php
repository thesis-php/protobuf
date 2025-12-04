<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Schema\Type\Visitor;

use Thesis\Protobuf\Internal\Schema\Type\ListT;
use Thesis\Protobuf\Internal\Schema\Type\MapT;

/**
 * @internal
 * @template T
 * @template-extends DefaultTypeSerializerVisitor<T>
 */
final class ListElementTypeSerializerVisitor extends DefaultTypeSerializerVisitor
{
    #[\Override]
    public function list(ListT $type): never
    {
        throw new \LogicException('List element type cannot be a list.');
    }

    #[\Override]
    public function map(MapT $type): never
    {
        throw new \LogicException('List element type cannot be a map.');
    }
}
